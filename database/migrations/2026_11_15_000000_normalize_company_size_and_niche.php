<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SIZES = [
        '1-10' => 'micro',
        '11-50' => 'small',
        '51-200' => 'medium',
        '201-500' => 'large',
        '501-1000' => 'large',
        '1001+' => 'large',
    ];

    private const NICHES = [
        'Eletrônicos' => 'eletronicos',
        'Alimentação' => 'alimentacao',
        'Moda' => 'moda',
        'Serviços domésticos' => 'servicos_domesticos',
        'Beleza' => 'beleza',
        'Educação' => 'educacao',
        'Casa' => 'casa',
        'Saúde' => 'saude',
    ];

    public function up(): void
    {
        foreach (self::SIZES as $old => $new) {
            DB::table('companies')->where('size', $old)->update(['size' => $new]);
        }

        foreach (self::NICHES as $label => $category) {
            DB::table('companies')->where('niche', $label)->update(['niche' => $category]);
        }
    }

    public function down(): void
    {
        foreach (array_flip(self::NICHES) as $category => $label) {
            DB::table('companies')->where('niche', $category)->update(['niche' => $label]);
        }

        foreach (['micro' => '1-10', 'small' => '11-50', 'medium' => '51-200', 'large' => '201-500'] as $new => $old) {
            DB::table('companies')->where('size', $new)->update(['size' => $old]);
        }
    }
};
