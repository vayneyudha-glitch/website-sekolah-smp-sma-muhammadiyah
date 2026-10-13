<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartOnlinePaymentRequest;
use App\Models\Payment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Services\Payments\PaymentAuditLogger;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentAuditLogger $audit,
    ) {}

    public function index(): View
    {
        $user = request()->user();
        $query = StudentInvoice::query()
            ->with(['studentProfile.user', 'studentProfile.schoolClass', 'payments' => fn ($payments) => $payments->latest()]);

        if ($user->role === \App\Models\User::ROLE_SISWA) {
            $studentProfileId = StudentProfile::query()->where('user_id', $user->id)->value('id');
            abort_if($studentProfileId === null, 404);
            $query->where('student_profile_id', $studentProfileId);
        } else {
            $childIds = $user->children()->pluck('users.id');
            $query->whereHas('studentProfile', fn ($students) => $students->whereIn('user_id', $childIds));
        }

        $invoices = $query->latest('due_date')->paginate(15);
        $invoices->getCollection()->each(fn (StudentInvoice $invoice) => Gate::authorize('view', $invoice));

        return view('payments.index', [
            'invoices' => $invoices,
            'gatewayAvailable' => $this->gateways->default()->isConfigured(),
        ]);
    }

    public function show(StudentInvoice $invoice): View
    {
        Gate::authorize('view', $invoice);
        $invoice->load(['studentProfile.user', 'studentProfile.schoolClass', 'payments' => fn ($payments) => $payments->latest()]);

        return view('payments.show', [
            'invoice' => $invoice,
            'gatewayAvailable' => $this->gateways->default()->isConfigured(),
        ]);
    }

    public function start(StartOnlinePaymentRequest $request, StudentInvoice $invoice): RedirectResponse
    {
        Gate::authorize('pay', $invoice);
        $gateway = $this->gateways->default();

        if (! $gateway->isConfigured()) {
            return back()->with('error', 'Pembayaran online belum tersedia. Silakan hubungi admin sekolah untuk pembayaran manual.');
        }

        $method = $request->validated('method');
        $provider = (string) config('payments.default');
        $payment = DB::transaction(function () use ($invoice, $provider, $method): Payment {
            $lockedInvoice = StudentInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_unless(
                in_array($lockedInvoice->status, [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_EXPIRED], true),
                409,
                'Tagihan ini tidak lagi tersedia untuk dibayar.',
            );

            $payment = $lockedInvoice->payments()->create([
                'method' => $method,
                'gateway' => $provider,
                'status' => Payment::STATUS_PENDING,
                'amount' => $lockedInvoice->amount,
            ]);
            $payment->update(['gateway_reference' => 'PAY-'.strtoupper(Str::random(20))]);
            $previousStatus = $lockedInvoice->status;
            $lockedInvoice->update(['status' => StudentInvoice::STATUS_PENDING]);

            $this->audit->record(request()->user(), 'payment_created', $payment, 'Memulai pembayaran online.', [
                'from' => null,
                'to' => Payment::STATUS_PENDING,
                'method' => $method,
                'provider' => $provider,
            ]);
            $this->audit->record(request()->user(), 'invoice_status_changed', $lockedInvoice, 'Status tagihan berubah menjadi menunggu pembayaran.', [
                'from' => $previousStatus,
                'to' => $lockedInvoice->status,
            ]);

            return $payment;
        });

        try {
            $checkout = $gateway->createCheckout(
                $invoice->load('studentProfile.user'),
                $payment,
                route(request()->user()->role.'.payments.index', absolute: true),
                $method,
            );
        } catch (PaymentGatewayException $exception) {
            DB::transaction(function () use ($payment, $invoice, $exception): void {
                $payment->refresh();
                $payment->update(['status' => Payment::STATUS_FAILED]);
                $lockedInvoice = StudentInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                if ($lockedInvoice->status === StudentInvoice::STATUS_PENDING) {
                    $previousStatus = $lockedInvoice->status;
                    $lockedInvoice->update(['status' => StudentInvoice::STATUS_UNPAID]);
                    $this->audit->record(request()->user(), 'invoice_status_changed', $lockedInvoice, 'Tagihan dikembalikan ke status belum bayar karena gateway gagal.', [
                        'from' => $previousStatus,
                        'to' => StudentInvoice::STATUS_UNPAID,
                    ]);
                }
                $this->audit->record(request()->user(), 'payment_status_changed', $payment, 'Pembayaran online gagal dimulai.', [
                    'from' => Payment::STATUS_PENDING,
                    'to' => Payment::STATUS_FAILED,
                    'reason' => $exception->getMessage(),
                ]);
            });

            return back()->with('error', $exception->getMessage());
        }

        $payment->update([
            'payment_url' => $checkout->url,
            'metadata' => ['provider_id' => $checkout->providerId],
        ]);

        return redirect()->away($checkout->url);
    }

    public function receipt(Payment $payment): Response
    {
        Gate::authorize('view', $payment->invoice);
        abort_unless($payment->status === Payment::STATUS_PAID, 404);

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            abort(503, 'Paket PDF belum tersedia. Silakan hubungi admin sekolah.');
        }

        $payment->load('invoice.studentProfile.user', 'invoice.studentProfile.schoolClass');

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.receipt', ['payment' => $payment])
            ->download($payment->receipt_number.'.pdf');
    }

}
