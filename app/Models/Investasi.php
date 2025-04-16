public function up(): void
{
    Schema::create('investasis', function (Blueprint $table) {
        $table->id();
        $table->string('nama_project');
        $table->double('biaya_investasi');
        $table->integer('periode_bulan');
        $table->double('pendapatan_per_bulan');
        $table->double('biaya_operasional'); // dalam persen
        $table->double('biaya_marketing');   // dalam persen
        $table->double('tingkat_diskonto');  // dalam persen
        $table->timestamps();
    });
}
