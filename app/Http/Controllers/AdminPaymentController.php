<?php

namespace App\Http\Controllers;

use App\Exports\PaymentsExport;
use App\Http\Requests\CancelStudentInvoiceRequest;
use App\Http\Requests\IssueStudentInvoicesRequest;
use App\Http\Requests\PaymentReportFilterRequest;
use App\Http\Requests\StoreBillTypeRequest;
use App\Http\Requests\StoreManualPaymentRequest;
use App\Models\AcademicYear;
use App\Models\BillType;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Services\Payments\PaymentAuditLogger;
use App\Services\SchoolNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentAuditLogger $audit,
        private readonly SchoolNotifier $notifier,
    ) {}

    public function index(PaymentReportFilterRequest $request): View
    {
        $filters = $request->validated();
        $month = $filters['month'] ?? now()->format('Y-m');
        $monthStart = now()->createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $payments = $this->paymentQuery($filters);
        $paidPayments = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->with(['invoice.studentProfile.schoolClass']);
        $selectedMonthPayments = (clone $paidPayments)->whereBetween('paid_at', [$monthStart, $monthEnd])->get();

        return view('admin.payments.index', [
            'payments' => $payments->paginate(20)->withQueryString(),
            'filters' => $filters,
            'month' => $month,
            'monthlySummary' => $paidPayments->get()
                ->groupBy(fn (Payment $payment): string => $payment->paid_at->format('Y-m'))
                ->map(fn ($records): int => (int) $records->sum('amount'))
                ->sortKeysDesc(),
            'classSummary' => $selectedMonthPayments
                ->groupBy(function (Payment $payment): string {
                    $class = $payment->invoice->studentProfile->schoolClass;

                    return $class->school_level.' '.$class->grade.' '.$class->name;
                })
                ->map(fn ($records): int => (int) $records->sum('amount'))
                ->sortKeys(),
            'openInvoices' => $this->invoiceQuery($filters)
                ->whereIn('status', [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_PENDING, StudentInvoice::STATUS_EXPIRED])
                ->orderBy('due_date')
                ->limit(30)
                ->get(),
            'classes' => SchoolClass::query()->with('academicYear')->orderBy('school_level')->orderBy('grade')->orderBy('name')->get(),
            'activeTypes' => BillType::query()->where('is_active', true)->with('academicYear')->orderBy('name')->get(),
            'paymentStatuses' => [
                Payment::STATUS_PENDING,
                Payment::STATUS_PAID,
                Payment::STATUS_FAILED,
                Payment::STATUS_EXPIRED,
                Payment::STATUS_CANCELLED,
            ],
        ]);
    }

    public function types(): View
    {
        return view('admin.payments.types', [
            'types' => BillType::query()->with('academicYear')->latest()->paginate(20),
            'academicYears' => AcademicYear::query()->orderByDesc('starts_on')->get(),
            'classes' => SchoolClass::query()->with('academicYear')->orderBy('school_level')->orderBy('grade')->orderBy('name')->get(),
            'activeTypes' => BillType::query()->where('is_active', true)->with('academicYear')->orderBy('name')->get(),
        ]);
    }

    public function storeType(StoreBillTypeRequest $request): RedirectResponse
    {
        $type = BillType::query()->create($request->validated());
        $this->audit->record($request->user(), 'created', $type, 'Membuat jenis tagihan '.$type->name, [
            'amount' => $type->amount,
            'academic_year_id' => $type->academic_year_id,
        ]);

        return back()->with('status', 'Jenis tagihan berhasil ditambahkan.');
    }

    public function updateType(StoreBillTypeRequest $request, BillType $billType): RedirectResponse
    {
        $before = $billType->only(['name', 'amount', 'academic_year_id', 'school_level', 'grade', 'due_date']);
        $billType->update($request->validated());
        $this->audit->record($request->user(), 'updated', $billType, 'Memperbarui jenis tagihan '.$billType->name, [
            'before' => $before,
            'after' => $billType->only(array_keys($before)),
        ]);

        return back()->with('status', 'Jenis tagihan berhasil diperbarui.');
    }

    public function archiveType(BillType $billType): RedirectResponse
    {
        $billType->update(['is_active' => false]);
        $this->audit->record(request()->user(), 'archived', $billType, 'Menonaktifkan jenis tagihan '.$billType->name);

        return back()->with('status', 'Jenis tagihan dinonaktifkan. Riwayat tagihan tetap tersimpan.');
    }

    public function issue(IssueStudentInvoicesRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = BillType::query()->findOrFail($data['bill_type_id']);
        $students = StudentProfile::query()
            ->whereHas('schoolClass', function (Builder $classes) use ($type, $data): void {
                $classes->where('academic_year_id', $type->academic_year_id);
                if ($type->school_level !== null) {
                    $classes->where('school_level', $type->school_level);
                }
                if ($type->grade !== null) {
                    $classes->where('grade', $type->grade);
                }
                if (isset($data['school_class_id'])) {
                    $classes->whereKey($data['school_class_id']);
                } else {
                    $classes->where('school_level', $data['school_level']);
                    if (filled($data['grade'] ?? null)) {
                        $classes->where('grade', $data['grade']);
                    }
                }
            })
            ->with(['schoolClass', 'user.parents'])
            ->get();

        $issued = 0;
        DB::transaction(function () use ($students, $type, $data, &$issued): void {
            foreach ($students as $student) {
                $invoice = StudentInvoice::query()->firstOrCreate(
                    [
                        'bill_type_id' => $type->id,
                        'student_profile_id' => $student->id,
                        'billing_period' => $data['billing_period'],
                    ],
                    [
                        'invoice_number' => 'INV-'.now()->format('ymd').'-'.strtoupper(Str::random(10)),
                        'bill_type_name' => $type->name,
                        'amount' => $type->amount,
                        'due_date' => $type->due_date,
                        'status' => StudentInvoice::STATUS_UNPAID,
                    ],
                );
                if ($invoice->wasRecentlyCreated) {
                    $invoice->setRelation('studentProfile', $student);
                    $this->notifier->invoice($invoice);
                }
                $issued += (int) $invoice->wasRecentlyCreated;
            }

            $this->audit->record(request()->user(), 'bulk_issued', $type, 'Menerbitkan tagihan massal untuk '.$data['billing_period'].'.', [
                'billing_period' => $data['billing_period'],
                'created_count' => $issued,
                'selected_student_count' => $students->count(),
            ]);
        });

        return back()->with('status', "Penerbitan selesai. Tagihan baru: {$issued}; tagihan yang sudah ada dilewati.");
    }

    public function storeManualPayment(StoreManualPaymentRequest $request, StudentInvoice $invoice): RedirectResponse
    {
        $data = $request->validated();
        abort_unless(
            ! in_array($invoice->status, [StudentInvoice::STATUS_PAID, StudentInvoice::STATUS_CANCELLED], true),
            409,
            'Tagihan sudah lunas atau dibatalkan.',
        );
        DB::transaction(function () use ($request, $invoice, $data): void {
            $lockedInvoice = StudentInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_unless(
                ! in_array($lockedInvoice->status, [StudentInvoice::STATUS_PAID, StudentInvoice::STATUS_CANCELLED], true),
                409,
                'Tagihan sudah lunas atau dibatalkan.',
            );

            $proofPath = $request->file('proof')->store('payment-proofs', 'local');
            if ($proofPath === false) {
                throw new RuntimeException('Bukti pembayaran tidak berhasil disimpan.');
            }

            foreach ($lockedInvoice->payments()->where('status', Payment::STATUS_PENDING)->lockForUpdate()->get() as $pendingPayment) {
                $pendingPayment->update(['status' => Payment::STATUS_CANCELLED]);
                $this->audit->record($request->user(), 'payment_status_changed', $pendingPayment, 'Pembayaran tertunda dibatalkan karena pembayaran manual dicatat.', [
                    'from' => Payment::STATUS_PENDING,
                    'to' => Payment::STATUS_CANCELLED,
                ]);
            }

            $payment = $lockedInvoice->payments()->create([
                'method' => $data['method'],
                'status' => Payment::STATUS_PAID,
                'amount' => $lockedInvoice->amount,
                'proof_path' => $proofPath,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);
            $payment->update([
                'receipt_number' => 'KWT-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT),
            ]);
            $this->audit->record($request->user(), 'payment_status_changed', $payment, 'Pembayaran manual dicatat sebagai lunas.', [
                'from' => null,
                'to' => Payment::STATUS_PAID,
                'method' => $data['method'],
                'amount' => $payment->amount,
            ]);

            $previousStatus = $lockedInvoice->status;
            $lockedInvoice->update(['status' => StudentInvoice::STATUS_PAID]);
            $this->notifier->paymentPaid($lockedInvoice);
            $this->audit->record($request->user(), 'invoice_status_changed', $lockedInvoice, 'Tagihan dilunasi melalui pembayaran manual.', [
                'from' => $previousStatus,
                'to' => StudentInvoice::STATUS_PAID,
                'payment_id' => $payment->id,
            ]);
        });

        return back()->with('status', 'Pembayaran manual berhasil dicatat dan masuk ke audit log.');
    }

    public function cancelInvoice(CancelStudentInvoiceRequest $request, StudentInvoice $invoice): RedirectResponse
    {
        DB::transaction(function () use ($request, $invoice): void {
            $lockedInvoice = StudentInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_unless(
                ! in_array($lockedInvoice->status, [StudentInvoice::STATUS_PAID, StudentInvoice::STATUS_CANCELLED], true),
                409,
                'Tagihan sudah lunas atau dibatalkan.',
            );

            foreach ($lockedInvoice->payments()->where('status', Payment::STATUS_PENDING)->lockForUpdate()->get() as $payment) {
                $payment->update(['status' => Payment::STATUS_CANCELLED]);
                $this->audit->record($request->user(), 'payment_status_changed', $payment, 'Pembayaran tertunda dibatalkan bersama tagihan.', [
                    'from' => Payment::STATUS_PENDING,
                    'to' => Payment::STATUS_CANCELLED,
                ]);
            }

            $previousStatus = $lockedInvoice->status;
            $lockedInvoice->update(['status' => StudentInvoice::STATUS_CANCELLED]);
            $this->audit->record($request->user(), 'invoice_status_changed', $lockedInvoice, 'Tagihan dibatalkan oleh admin.', [
                'from' => $previousStatus,
                'to' => StudentInvoice::STATUS_CANCELLED,
                'reason' => $request->validated('reason'),
            ]);
        });

        return back()->with('status', 'Tagihan berhasil dibatalkan.');
    }

    public function export(PaymentReportFilterRequest $request): BinaryFileResponse
    {
        return Excel::download(
            new PaymentsExport($this->paymentQuery($request->validated())),
            'rekap-pembayaran-'.$request->validated('month', now()->format('Y-m')).'.xlsx',
        );
    }

    public function downloadProof(Payment $payment): Response
    {
        abort_if($payment->proof_path === null, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($payment->proof_path), 404);

        return $disk->download($payment->proof_path, 'bukti-pembayaran-'.$payment->id.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** @param array<string, mixed> $filters
     *  @return Builder<Payment>
     */
    private function paymentQuery(array $filters): Builder
    {
        return Payment::query()
            ->with(['invoice.studentProfile.user', 'invoice.studentProfile.schoolClass'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['school_class_id'] ?? null, fn (Builder $query, int $classId) => $query->whereHas('invoice.studentProfile', fn (Builder $student) => $student->where('school_class_id', $classId)))
            ->when($filters['school_level'] ?? null, fn (Builder $query, string $level) => $query->whereHas('invoice.studentProfile.schoolClass', fn (Builder $class) => $class->where('school_level', $level)))
            ->when($filters['month'] ?? null, function (Builder $query, string $month): void {
                $start = now()->createFromFormat('Y-m', $month)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $query->where(function (Builder $period) use ($start, $end): void {
                    $period->whereBetween('payments.paid_at', [$start, $end])
                        ->orWhere(fn (Builder $unpaid) => $unpaid
                            ->whereNull('payments.paid_at')
                            ->whereBetween('payments.created_at', [$start, $end]));
                });
            })
            ->latest('payments.created_at');
    }

    /** @param array<string, mixed> $filters
     *  @return Builder<StudentInvoice>
     */
    private function invoiceQuery(array $filters): Builder
    {
        return StudentInvoice::query()
            ->with(['studentProfile.user', 'studentProfile.schoolClass'])
            ->when($filters['invoice_status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['school_class_id'] ?? null, fn (Builder $query, int $classId) => $query->whereHas('studentProfile', fn (Builder $student) => $student->where('school_class_id', $classId)))
            ->when($filters['school_level'] ?? null, fn (Builder $query, string $level) => $query->whereHas('studentProfile.schoolClass', fn (Builder $class) => $class->where('school_level', $level)));
    }
}
