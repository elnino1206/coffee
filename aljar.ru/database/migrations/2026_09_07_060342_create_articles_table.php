<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');

            // Рубрика строкой, а не перечислением: по доменной модели
            // (раздел 8) ещё не решено, останется ли список фиксированным
            // или контентщик заведёт свои. Строка переживёт оба решения,
            // перечисление пришлось бы ломать миграцией.
            $table->string('tag');

            $table->string('excerpt');

            // Абзацы разделяются пустой строкой. Хранить их массивом в
            // JSON значило бы заставить редактора думать о разметке —
            // здесь он просто пишет текст.
            $table->text('body')->nullable();

            $table->string('cover_path')->nullable();

            $table->string('status');

            // Дата публикации отдельно от created_at: черновик заводят
            // заранее, а на витрине статья должна встать своей датой.
            $table->timestamp('published_at')->nullable();

            // Автор остаётся при удалении сотрудника: подпись под
            // материалом не должна исчезать вместе с учётной записью.
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
