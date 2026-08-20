<?php

namespace Tests\Feature;

use App\Exports\WorkerImportTemplate;
use App\Imports\WorkerImport;
use App\Livewire\Master\RegisterWorker;
use App\Models\Bank;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkerFeatureSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new PermissionSeeder)->run();
        Role::firstOrCreate(['name' => 'super-admin'])
            ->syncPermissions(Permission::all());
    }

    public function test_banks_page_renders(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get('/master/banks')
            ->assertOk();
    }

    public function test_workers_page_renders(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get('/master/workers')
            ->assertOk();
    }

    public function test_worker_detail_page_renders(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $worker = Worker::create([
            'ops_id' => 'OPS-001',
            'full_name' => 'Budi',
            'nik' => '1234567890123456',
        ]);

        $this->actingAs($user)
            ->get('/master/workers/'.$worker->id)
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('OPS-001');
    }

    public function test_worker_create_and_bank_relation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $bank = Bank::create([
            'name' => 'Bank Test',
            'initial' => 'BT',
        ]);

        $worker = Worker::create([
            'ops_id' => 'OPS-001',
            'full_name' => 'Budi',
            'nik' => '1234567890123456',
            'bank_id' => $bank->id,
            'account_number' => '123456',
        ]);

        $this->assertDatabaseHas('workers', ['ops_id' => 'OPS-001']);
        $this->assertEquals('Bank Test', $worker->bank->name);
    }

    public function test_worker_template_has_three_sheets(): void
    {
        Bank::create(['name' => 'Bank Test', 'initial' => 'BT']);

        Excel::store(new WorkerImportTemplate, 'test-template.xlsx', 'local');
        $path = Storage::disk('local')->path('test-template.xlsx');

        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($path);
        $sheetNames = $spreadsheet->getSheetNames();

        $this->assertCount(3, $sheetNames);
        $this->assertContains('Data Worker', $sheetNames);
        $this->assertContains('Daftar Bank', $sheetNames);
        $this->assertContains('Cara Pengisian', $sheetNames);

        Storage::disk('local')->delete('test-template.xlsx');
    }

    public function test_worker_import_strips_apostrophe_and_keeps_numbers(): void
    {
        $import = new WorkerImport;

        $import->model([
            'ops_id' => "'OPS-002",
            'full_name' => 'Test Worker',
            'nik' => "'081234567890",
            'phone_number' => "'081312345678",
            'account_number' => "'1234567890",
            'domicile_address' => 'Jl. Test',
            'emergency_contact_number' => "'081111222333",
            'emergency_contact_name' => 'Orang',
            'emergency_contact_relation' => 'Suami',
        ]);

        $worker = Worker::where('ops_id', 'OPS-002')->first();
        $this->assertNotNull($worker);
        $this->assertEquals('081234567890', $worker->nik);
        $this->assertEquals('081312345678', $worker->phone_number);
        $this->assertEquals('1234567890', $worker->account_number);
        $this->assertEquals('081111222333', $worker->emergency_contact_number);
    }

    public function test_worker_import_reads_only_first_sheet(): void
    {
        Bank::firstOrCreate(['name' => 'Bank Central Asia'], ['initial' => 'BCA']);

        Excel::store(new WorkerImportTemplate, 'flow.xlsx', 'local');
        $path = Storage::disk('local')->path('flow.xlsx');

        Excel::import(new WorkerImport, $path);

        $worker = Worker::first();
        $this->assertNotNull($worker);
        $this->assertEquals('OPS-001', $worker->ops_id);
        $this->assertEquals('3171234567890001', $worker->nik);
        $this->assertEquals('081234567890', $worker->phone_number);
        $this->assertEquals('Bank Central Asia', $worker->bank->name);
        $this->assertSame(1, Worker::count());

        Storage::disk('local')->delete('flow.xlsx');
    }

    public function test_dashboard_renders_with_real_data(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('super-admin');

        Worker::create([
            'ops_id' => 'DASH-001',
            'full_name' => 'Dash Worker',
            'nik' => '1111111111111111',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Total Pekerja')
            ->assertSee('Pekerja Baru Bulan Ini')
            ->assertSee('Dash Worker');
    }

    public function test_register_worker_page_renders(): void
    {
        $this->get('/register-worker')->assertOk();
    }

    public function test_register_worker_can_self_register(): void
    {
        $bank = Bank::create(['name' => 'Bank Test', 'initial' => 'BT']);

        Livewire::test(RegisterWorker::class)
            ->set('opsId', 'OPS-SELF-001')
            ->set('fullName', 'Self Register')
            ->set('nik', '3201010101010101')
            ->set('phoneNumber', '081234567890')
            ->set('bankId', $bank->id)
            ->set('accountNumber', '1234567890')
            ->set('domicileAddress', 'Jl. Test')
            ->set('emergencyContactNumber', '081111222333')
            ->set('emergencyContactName', 'Orang Tua')
            ->set('emergencyContactRelation', 'Suami')
            ->set('ktpPhoto', UploadedFile::fake()->image('ktp.jpg'))
            ->set('selfPhoto', UploadedFile::fake()->image('self.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('workers', ['ops_id' => 'OPS-SELF-001']);
    }

    public function test_register_worker_requires_all_fields(): void
    {
        Livewire::test(RegisterWorker::class)
            ->set('opsId', 'OPS-SELF-003')
            ->call('save')
            ->assertHasErrors([
                'fullName',
                'nik',
                'phoneNumber',
                'bankId',
                'accountNumber',
                'domicileAddress',
                'emergencyContactNumber',
                'emergencyContactName',
                'emergencyContactRelation',
                'ktpPhoto',
                'selfPhoto',
            ]);
    }

    public function test_register_worker_rejects_duplicate_ops_id(): void
    {
        $bank = Bank::create(['name' => 'Bank Test', 'initial' => 'BT']);

        Worker::create([
            'ops_id' => 'OPS-SELF-002',
            'full_name' => 'Existing',
            'nik' => '3201010101010202',
        ]);

        Livewire::test(RegisterWorker::class)
            ->set('opsId', 'OPS-SELF-002')
            ->set('fullName', 'Duplicate')
            ->set('nik', '3201010101010303')
            ->set('phoneNumber', '081234567890')
            ->set('bankId', $bank->id)
            ->set('accountNumber', '1234567890')
            ->set('domicileAddress', 'Jl. Test')
            ->set('emergencyContactNumber', '081111222333')
            ->set('emergencyContactName', 'Orang Tua')
            ->set('emergencyContactRelation', 'Suami')
            ->set('ktpPhoto', UploadedFile::fake()->image('ktp.jpg'))
            ->set('selfPhoto', UploadedFile::fake()->image('self.jpg'))
            ->call('save')
            ->assertHasErrors('opsId');
    }
}
