<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BillType;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Models\SchoolClass;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config([
            'payments.default' => 'midtrans',
            'payments.currency' => 'IDR',
            'payments.midtrans.server_key' => 'test-server-key',
            'payments.midtrans.api_url' => 'https://app.sandbox.midtrans.com',
        ]);
    }

    public function test_students_and_parents_can_only_view_their_own_authorized_invoices(): void
    {
        [$class, $year] = $this->schoolClass();
        $student = $this->studentIn($class, 'Murid Pemilik');
        $otherStudent = $this->studentIn($class, 'Murid Lain');
        $invoice = $this->invoiceFor($student, $year);
        $otherInvoice = $this->invoiceFor($otherStudent, $year);

        $parent = User::factory()->create();
        $parent->forceFill(['role' => User::ROLE_ORANG_TUA])->save();
        $parent->children()->attach($student->id);
        $unrelatedParent = User::factory()->create();
        $unrelatedParent->forceFill(['role' => User::ROLE_ORANG_TUA])->save();

        $this->actingAs($student)->get(route('siswa.payments.show', $invoice))->assertOk();
        $this->actingAs($student)->get(route('siswa.payments.show', $otherInvoice))->assertForbidden();
        $this->actingAs($student)->get(route('siswa.payments.index'))->assertSee($invoice->invoice_number)->assertDontSee($otherInvoice->invoice_number);
        $this->actingAs($parent)->get(route('orang_tua.payments.show', $invoice))->assertOk();
        $this->actingAs($unrelatedParent)->get(route('orang_tua.payments.show', $invoice))->assertForbidden();
        $this->actingAs($parent)->get(route('orang_tua.payments.index'))->assertSee($invoice->invoice_number)->assertDontSee($otherInvoice->invoice_number);
        $this->actingAs($student)->get(route('admin.payments.index'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk();
    }

    public function test_bill_type_page_disables_mass_issuance_when_no_active_types_exist(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->get(route('admin.payments.types'))
            ->assertOk()
            ->assertSee('Belum ada jenis tagihan aktif')
            ->assertSee('<select id="issue_bill_type_id" name="bill_type_id" required disabled', false)
            ->assertSee('<button disabled', false);
    }

    public function test_a_valid_midtrans_webhook_marks_an_invoice_paid_and_duplicate_notifications_are_idempotent(): void
    {
        [$class, $year] = $this->schoolClass();
        $student = $this->studentIn($class, 'Murid Pembayaran');
        $invoice = $this->invoiceFor($student, $year);
        $payment = $invoice->payments()->create([
            'method' => 'virtual_account',
            'gateway' => 'midtrans',
            'gateway_reference' => 'PAY-TEST-100',
            'status' => Payment::STATUS_PENDING,
            'amount' => $invoice->amount,
        ]);
        $invoice->update(['status' => StudentInvoice::STATUS_PENDING]);
        $body = $this->signedMidtransNotification($payment->gateway_reference, '250000.00');

        $this->postMidtransWebhook($body)->assertOk();
        $this->postMidtransWebhook($body)->assertOk();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->receipt_number);
        $this->assertSame(StudentInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(1, $invoice->payments()->where('status', Payment::STATUS_PAID)->count());
        $this->assertSame(2, PaymentWebhookLog::query()->count());
        $this->assertSame('duplikat', PaymentWebhookLog::query()->latest('id')->value('processing_status'));
    }

    public function test_an_invalid_midtrans_signature_is_logged_without_changing_payment_status(): void
    {
        [$class, $year] = $this->schoolClass();
        $student = $this->studentIn($class, 'Murid Tertunda');
        $invoice = $this->invoiceFor($student, $year);
        $payment = $invoice->payments()->create([
            'method' => 'qris',
            'gateway' => 'midtrans',
            'gateway_reference' => 'PAY-TEST-101',
            'status' => Payment::STATUS_PENDING,
            'amount' => $invoice->amount,
        ]);

        $body = $this->signedMidtransNotification($payment->gateway_reference, '250000.00', 'invalid-signature');
        $this->postMidtransWebhook($body)->assertUnauthorized();

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertFalse(PaymentWebhookLog::query()->firstOrFail()->signature_valid);
        $this->assertSame('tanda_tangan_tidak_valid', PaymentWebhookLog::query()->value('processing_status'));
    }

    public function test_a_verified_webhook_with_the_wrong_amount_does_not_mark_the_invoice_paid(): void
    {
        [$class, $year] = $this->schoolClass();
        $student = $this->studentIn($class, 'Murid Nominal');
        $invoice = $this->invoiceFor($student, $year);
        $payment = $invoice->payments()->create([
            'method' => 'qris',
            'gateway' => 'midtrans',
            'gateway_reference' => 'PAY-TEST-102',
            'status' => Payment::STATUS_PENDING,
            'amount' => $invoice->amount,
        ]);

        $body = $this->signedMidtransNotification($payment->gateway_reference, '250001.00');
        $this->postMidtransWebhook($body)->assertStatus(422);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame(StudentInvoice::STATUS_UNPAID, $invoice->fresh()->status);
        $this->assertSame('nominal_tidak_cocok', PaymentWebhookLog::query()->value('processing_status'));
    }

    public function test_mass_issuance_targets_only_the_selected_class_and_skips_existing_periods(): void
    {
        [$class, $year] = $this->schoolClass();
        $otherClass = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII B',
            'grade' => '7',
            'school_level' => 'SMP',
        ]);
        $studentOne = $this->studentIn($class, 'Murid Satu');
        $studentTwo = $this->studentIn($class, 'Murid Dua');
        $this->studentIn($otherClass, 'Murid Kelas Lain');
        $type = BillType::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'SPP Bulanan',
            'amount' => 250000,
            'school_level' => 'SMP',
            'grade' => '7',
            'due_date' => now()->addDays(10)->toDateString(),
            'is_active' => true,
        ]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $data = [
            'bill_type_id' => $type->id,
            'school_class_id' => $class->id,
            'school_level' => '',
            'grade' => '',
            'billing_period' => '2026-10',
        ];

        $this->actingAs($admin)->post(route('admin.payments.issue'), $data)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.payments.issue'), $data)->assertRedirect();

        $this->assertSame(2, StudentInvoice::query()->count());
        $this->assertSame(1, StudentInvoice::query()->where('student_profile_id', $studentOne->studentProfile->id)->count());
        $this->assertSame(1, StudentInvoice::query()->where('student_profile_id', $studentTwo->studentProfile->id)->count());
        $this->assertDatabaseMissing('student_invoices', [
            'bill_type_id' => $type->id,
            'student_profile_id' => $otherClass->students()->value('id'),
        ]);
    }

    private function schoolClass(): array
    {
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $class = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII A',
            'grade' => '7',
            'school_level' => 'SMP',
        ]);

        return [$class, $year];
    }

    private function studentIn(SchoolClass $class, string $name): User
    {
        $student = User::factory()->create(['name' => $name]);
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'S-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'nis' => 'N-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'school_class_id' => $class->id,
            'status' => 'aktif',
        ]);

        return $student->load('studentProfile');
    }

    private function invoiceFor(User $student, AcademicYear $year): StudentInvoice
    {
        $type = BillType::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'SPP Bulanan',
            'amount' => 250000,
            'school_level' => 'SMP',
            'grade' => '7',
            'due_date' => now()->addDays(10)->toDateString(),
            'is_active' => true,
        ]);

        return StudentInvoice::query()->create([
            'bill_type_id' => $type->id,
            'student_profile_id' => $student->studentProfile->id,
            'invoice_number' => 'INV-'.str_pad((string) $student->id, 10, '0', STR_PAD_LEFT),
            'bill_type_name' => $type->name,
            'billing_period' => '2026-10',
            'amount' => $type->amount,
            'due_date' => $type->due_date,
            'status' => StudentInvoice::STATUS_UNPAID,
        ]);
    }

    private function signedMidtransNotification(string $reference, string $amount, ?string $signatureOverride = null): string
    {
        $payload = [
            'order_id' => $reference,
            'status_code' => '200',
            'gross_amount' => $amount,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = $signatureOverride ?? hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'test-server-key',
        );

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function postMidtransWebhook(string $body)
    {
        return $this->call(
            'POST',
            route('payments.webhook', ['provider' => 'midtrans']),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body,
        );
    }
}
