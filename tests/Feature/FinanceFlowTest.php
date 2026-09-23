<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_an_initial_account(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_name' => 'BCA',
            'opening_balance' => 2000000,
            'currency' => 'IDR',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'budi@example.test')->first());
        $this->assertDatabaseHas('accounts', ['name' => 'BCA', 'opening_balance' => 2000000]);
        $this->get(route('dashboard'))->assertOk()->assertSee('TOTAL SALDO');
    }

    public function test_income_expense_and_transfer_keep_balances_accurate(): void
    {
        $user = User::factory()->create();
        $bca = $user->accounts()->create(['name' => 'BCA', 'opening_balance' => 2000000]);
        $cash = $user->accounts()->create(['name' => 'Cash', 'opening_balance' => 0]);
        $this->actingAs($user);

        $this->post(route('transactions.store'), $this->transaction('income', 5000000, $bca->id));
        $this->post(route('transactions.store'), $this->transaction('expense', 500000, $bca->id));
        $this->post(route('transactions.store'), $this->transaction('transfer', 1000000, $bca->id, $cash->id));

        $balances = app(AccountBalanceService::class);
        $this->assertSame(5500000, $balances->for($bca));
        $this->assertSame(1000000, $balances->for($cash));
        $this->assertSame(6500000, $balances->totalForUser($user->id));
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $account = $owner->accounts()->create(['name' => 'BCA']);
        $transaction = $owner->transactions()->create([
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 1000,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs(User::factory()->create())
            ->delete(route('transactions.destroy', $transaction))
            ->assertForbidden();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_reports_use_real_transactions_and_support_custom_dates(): void
    {
        $user = User::factory()->create();
        $account = $user->accounts()->create(['name' => 'BCA']);
        $user->transactions()->create([
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 5000000,
            'transaction_date' => '2026-09-10',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 1500000,
            'transaction_date' => '2026-09-15',
        ]);
        $this->actingAs($user);

        $this->get(route('reports', ['period' => 'custom', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30']))
            ->assertOk()
            ->assertSee('3.500.000')
            ->assertSee('5.000.000')
            ->assertSee('1.500.000');

        $this->get(route('reports', ['period' => 'custom', 'start_date' => '2026-09-01', 'end_date' => '2026-09-12']))
            ->assertOk()
            ->assertSee('5.000.000')
            ->assertDontSee('1.500.000');

        $this->get(route('reports.export', ['period' => 'custom', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'search' => 'BCA']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_user_can_create_and_update_a_savings_goal(): void
    {
        $user = User::factory()->create();
        $account = $user->accounts()->create(['name' => 'Gaji', 'opening_balance' => 5000000]);
        $savingsAccount = $user->accounts()->create(['name' => 'BCA blu', 'opening_balance' => 0]);
        $this->actingAs($user);

        $this->post(route('goals.store'), [
            'name' => 'Laptop',
            'target_amount' => 10000000,
            'deadline' => '2026-12-31',
            'color' => '#126E73',
        ])->assertRedirect();

        $goal = $user->savingsGoals()->first();
        $this->post(route('goals.contribute', $goal), ['type' => 'deposit', 'account_id' => $account->id, 'destination_account_id' => $savingsAccount->id, 'amount' => 4000000])
            ->assertRedirect();
        $this->assertSame(40, $goal->fresh()->progress);

        $this->post(route('goals.contribute', $goal), ['type' => 'withdrawal', 'account_id' => $savingsAccount->id, 'destination_account_id' => $account->id, 'amount' => 1000000])
            ->assertRedirect();
        $this->assertSame(3000000, $goal->fresh()->saved_amount);
        $this->assertSame(2000000, app(AccountBalanceService::class)->for($account->fresh()));
        $this->assertDatabaseCount('savings_transactions', 2);
    }

    public function test_user_cannot_contribute_to_another_users_goal(): void
    {
        $owner = User::factory()->create();
        $goal = $owner->savingsGoals()->create(['name' => 'Dana darurat', 'target_amount' => 1000000]);

        $this->actingAs(User::factory()->create())
            ->post(route('goals.contribute', $goal), ['type' => 'deposit', 'amount' => 500000])
            ->assertForbidden();
    }

    public function test_goals_and_more_pages_are_available_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('goals'))->assertOk()->assertSee('Target tabungan');
        $this->get(route('more'))->assertOk()->assertSee('Pengaturan');
    }

    public function test_user_can_add_a_bank_account_for_future_transactions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => 2500000,
            'color' => '#126E73',
        ])->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => 2500000,
        ]);
    }

    public function test_user_can_delete_one_of_multiple_accounts(): void
    {
        $user = User::factory()->create();
        $first = $user->accounts()->create(['name' => 'BCA']);
        $duplicate = $user->accounts()->create(['name' => 'BCA']);

        $this->actingAs($user)
            ->delete(route('accounts.destroy', $duplicate))
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', ['id' => $first->id]);
        $this->assertDatabaseMissing('accounts', ['id' => $duplicate->id]);
    }

    public function test_user_cannot_delete_the_last_account(): void
    {
        $user = User::factory()->create();
        $account = $user->accounts()->create(['name' => 'Dompet']);

        $this->actingAs($user)
            ->delete(route('accounts.destroy', $account))
            ->assertStatus(422);

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_user_can_create_and_settle_a_debt_record(): void
    {
        $user = User::factory()->create();
        $account = $user->accounts()->create(['name' => 'BCA']);
        $this->actingAs($user);

        $this->get(route('debts'))->assertOk()->assertSee('Hutang & Piutang', false);
        $this->post(route('debts.store'), [
            'type' => 'debt',
            'person' => 'Rina',
            'amount' => 750000,
            'due_date' => '2026-10-01',
            'note' => 'Pinjaman kos',
        ])->assertRedirect();

        $debt = $user->debts()->first();
        $this->assertDatabaseHas('debts', ['id' => $debt->id, 'status' => 'unpaid']);

        $this->post(route('debts.payment', $debt), [
            'amount' => 250000,
            'account_id' => $account->id,
            'paid_at' => '2026-09-24',
            'note' => 'Cicilan pertama',
        ])->assertRedirect();
        $this->assertDatabaseHas('debt_payments', ['debt_id' => $debt->id, 'amount' => 250000]);
        $this->assertDatabaseHas('debts', ['id' => $debt->id, 'status' => 'unpaid']);

        $this->post(route('debts.payment', $debt), [
            'amount' => 500000,
            'account_id' => $account->id,
            'paid_at' => '2026-10-01',
        ])->assertRedirect();
        $this->assertDatabaseHas('debts', ['id' => $debt->id, 'status' => 'settled']);
    }

    public function test_user_can_manage_custom_categories(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('categories.store'), [
            'name' => 'Kesehatan',
            'type' => 'expense',
            'color' => '#126E73',
        ])->assertRedirect();

        $category = $user->categories()->where('name', 'Kesehatan')->firstOrFail();
        $this->put(route('categories.update', $category), [
            'name' => 'Kesehatan & Obat',
            'type' => 'expense',
            'color' => '#ED806E',
        ])->assertRedirect();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Kesehatan & Obat']);

        $this->delete(route('categories.destroy', $category))->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_can_update_profile_and_currency_settings(): void
    {
        $user = User::factory()->create(['name' => 'Lama', 'email' => 'lama@example.test']);

        $this->actingAs($user)->put(route('settings.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'currency' => 'USD',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'currency' => 'USD',
        ]);
    }

    private function transaction(string $type, int $amount, int $accountId, ?int $destinationId = null): array
    {
        return [
            'type' => $type,
            'amount' => $amount,
            'account_id' => $accountId,
            'destination_account_id' => $destinationId,
            'transaction_date' => now()->toDateString(),
        ];
    }
}