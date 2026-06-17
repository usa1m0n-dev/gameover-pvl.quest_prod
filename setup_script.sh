#!/bin/bash

echo "🚀 Начинаем настройку проекта под Filament..."

# --- 1. МИГРАЦИИ ---

# Очистка старых миграций (кроме users/jobs)
rm database/migrations/2025_* 2>/dev/null

create_migration() {
    php artisan make:migration "$1" --create="$2" > /dev/null
    sleep 1 # Ждем, чтобы таймштампы отличались
}

echo "📦 Создаем миграции..."

# 1. Guests
create_migration "create_guests_table" "guests"
LATEST=$(ls -t database/migrations/*.php | head -n 1)
cat <<EOT > "$LATEST"
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('guests', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->nullable();
            \$table->string('phone')->unique();
            \$table->date('birthday')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('guests'); }
};
EOT

# 2. Employees
create_migration "create_employees_table" "employees"
LATEST=$(ls -t database/migrations/*.php | head -n 1)
cat <<EOT > "$LATEST"
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('phone')->nullable();
            \$table->date('hire_date');
            \$table->string('position')->nullable(); 
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('employees'); }
};
EOT

# 3. Points
create_migration "create_points_table" "points"
LATEST=$(ls -t database/migrations/*.php | head -n 1)
cat <<EOT > "$LATEST"
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('points', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('address');
            \$table->text('description')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('points'); }
};
EOT

# 4. Party Rooms
create_migration "create_party_rooms_table" "party_rooms"
LATEST=$(ls -t database/migrations/*.php | head -n 1)
cat <<EOT > "$LATEST"
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('party_rooms', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('point_id')->constrained('points')->cascadeOnDelete();
            \$table->unsignedInteger('area');
            \$table->unsignedInteger('price_per_hour'); // копейки
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('party_rooms'); }
};
EOT

# 5. Activities
create_migration "create_activities_table" "activities"
LATEST=$(ls -t database/migrations/*.php | head -n 1)
cat <<EOT > "$LATEST"
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activities', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->unsignedInteger('base_price');
            \$table->unsignedInteger('employee_rate');
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('activities'); }
};
EOT

# 6. Records
create_migration "create_records_table" "records"
LATEST=$(ls -t database/migrations/*.php | head -n 1)
cat <<EOT > "$LATEST"
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('records', function (Blueprint \$table) {
            \$table->id();
            \$table->dateTime('datetime');
            \$table->foreignId('guest_id')->constrained('guests');
            \$table->foreignId('point_id')->constrained('points');
            \$table->foreignId('room_id')->nullable()->constrained('party_rooms')->nullOnDelete();
            
            \$table->unsignedTinyInteger('estimated_room_time')->default(0);
            \$table->unsignedInteger('fixed_room_price')->nullable();
            
            \$table->unsignedInteger('total')->default(0);
            \$table->unsignedInteger('prepaid')->default(0);
            
            \$table->string('status')->default('new');
            \$table->string('source')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('records'); }
};
EOT

# 7. Record Activities (Pivot with ID)
create_migration "create_record_activities_table" "record_activities"
LATEST=$(ls -t database/migrations/*.php | head -n 1)
cat <<EOT > "$LATEST"
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('record_activities', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('record_id')->constrained('records')->cascadeOnDelete();
            \$table->foreignId('activity_id')->constrained('activities');
            
            \$table->unsignedTinyInteger('players_count')->default(4);
            \$table->unsignedInteger('fixed_price')->nullable();
            \$table->decimal('discount_multiplier', 5, 2)->default(1.00);
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('record_activities'); }
};
EOT


# --- 2. МОДЕЛИ ---

echo "🏗 Генерируем модели..."

# Guest
cat <<EOT > app/Models/Guest.php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Guest extends Model {
    protected \$guarded = ['id'];
}
EOT

# Employee
cat <<EOT > app/Models/Employee.php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
    protected \$guarded = ['id'];
}
EOT

# Point
cat <<EOT > app/Models/Point.php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Point extends Model {
    protected \$guarded = ['id'];
    public function rooms() { return \$this->hasMany(PartyRoom::class); }
}
EOT

# PartyRoom
cat <<EOT > app/Models/PartyRoom.php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartyRoom extends Model {
    protected \$guarded = ['id'];
    public function point() { return \$this->belongsTo(Point::class); }
    
    // Мутаторы денег (копейки <-> рубли)
    public function setPricePerHourAttribute(\$val) { \$this->attributes['price_per_hour'] = \$val * 100; }
    public function getPricePerHourAttribute(\$val) { return \$val / 100; }
}
EOT

# Activity
cat <<EOT > app/Models/Activity.php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model {
    protected \$guarded = ['id'];
    public function setBasePriceAttribute(\$val) { \$this->attributes['base_price'] = \$val * 100; }
    public function getBasePriceAttribute(\$val) { return \$val / 100; }
}
EOT

# RecordActivity (Pivot Model)
cat <<EOT > app/Models/RecordActivity.php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecordActivity extends Model {
    protected \$guarded = ['id'];
    protected \$table = 'record_activities';
    
    public function activity() { return \$this->belongsTo(Activity::class); }
    public function record() { return \$this->belongsTo(Record::class); }
    
    public function setFixedPriceAttribute(\$val) { \$this->attributes['fixed_price'] = \$val * 100; }
    public function getFixedPriceAttribute(\$val) { return \$val / 100; }
}
EOT

# Record (Main)
cat <<EOT > app/Models/Record.php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Record extends Model {
    protected \$guarded = ['id'];

    public function guest() { return \$this->belongsTo(Guest::class); }
    public function point() { return \$this->belongsTo(Point::class); }
    public function room() { return \$this->belongsTo(PartyRoom::class); }
    
    // ВАЖНО для Filament Repeater: связь с таблицей record_activities как HasMany
    public function recordActivities() { return \$this->hasMany(RecordActivity::class); }
    
    public function setTotalAttribute(\$val) { \$this->attributes['total'] = \$val * 100; }
    public function getTotalAttribute(\$val) { return \$val / 100; }
    public function setPrepaidAttribute(\$val) { \$this->attributes['prepaid'] = \$val * 100; }
    public function getPrepaidAttribute(\$val) { return \$val / 100; }
}
EOT

echo "✅ Готово! Запускай php artisan migrate и ставь Filament."
