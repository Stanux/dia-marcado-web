<?php

namespace Tests\Unit\Services;

use App\Services\FeeCalculator;
use App\ValueObjects\PaymentAmounts;
use Tests\TestCase;

class FeeCalculatorTest extends TestCase
{
    private FeeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new FeeCalculator();
    }

    /** @test */
    public function calcula_valores_corretamente_quando_noivos_pagam_taxa()
    {
        // Arrange: Presente de R$ 100,00 com taxa de 5%
        $precoOriginal = 10000; // R$ 100,00 em centavos
        $porcentagemTaxa = 0.05; // 5%
        $modalidade = 'couple_pays';

        // Act
        $resultado = $this->calculator->calculate($precoOriginal, $porcentagemTaxa, $modalidade);

        // Assert
        $this->assertInstanceOf(PaymentAmounts::class, $resultado);
        $this->assertEquals(10000, $resultado->displayPrice, 'Preço exibido deve ser o original');
        $this->assertEquals(10000, $resultado->grossAmount, 'Valor bruto deve ser o original');
        $this->assertEquals(500, $resultado->feeAmount, 'Taxa deve ser 5% de R$ 100,00 = R$ 5,00');
        $this->assertEquals(9500, $resultado->netAmountCouple, 'Noivos recebem R$ 95,00');
        $this->assertEquals(500, $resultado->platformAmount, 'Plataforma recebe R$ 5,00');
    }

    /** @test */
    public function calcula_valores_corretamente_quando_convidados_pagam_taxa()
    {
        // Arrange: Presente de R$ 100,00 com taxa de 5%
        $precoOriginal = 10000; // R$ 100,00 em centavos
        $porcentagemTaxa = 0.05; // 5%
        $modalidade = 'guest_pays';

        // Act
        $resultado = $this->calculator->calculate($precoOriginal, $porcentagemTaxa, $modalidade);

        // Assert
        $this->assertInstanceOf(PaymentAmounts::class, $resultado);
        // R$ 100,00 / (1 - 0.05) = R$ 100,00 / 0.95 = R$ 105,26
        $this->assertEquals(10526, $resultado->displayPrice, 'Preço exibido deve incluir a taxa');
        $this->assertEquals(10526, $resultado->grossAmount, 'Valor bruto é o preço majorado');
        $this->assertEquals(10000, $resultado->netAmountCouple, 'Noivos recebem o valor original');
        $this->assertEquals(526, $resultado->platformAmount, 'Plataforma recebe a diferença');
        $this->assertEquals(526, $resultado->feeAmount, 'Taxa é igual ao valor da plataforma');
    }

    /** @test */
    public function calcula_display_price_corretamente_para_couple_pays()
    {
        // Arrange
        $precoOriginal = 10000;
        $porcentagemTaxa = 0.05;
        $modalidade = 'couple_pays';

        // Act
        $precoExibido = $this->calculator->calculateDisplayPrice($precoOriginal, $porcentagemTaxa, $modalidade);

        // Assert
        $this->assertEquals(10000, $precoExibido, 'Deve retornar o preço original');
    }

    /** @test */
    public function calcula_display_price_corretamente_para_guest_pays()
    {
        // Arrange
        $precoOriginal = 10000;
        $porcentagemTaxa = 0.05;
        $modalidade = 'guest_pays';

        // Act
        $precoExibido = $this->calculator->calculateDisplayPrice($precoOriginal, $porcentagemTaxa, $modalidade);

        // Assert
        $this->assertEquals(10526, $precoExibido, 'Deve retornar o preço majorado');
    }

    /** @test */
    public function lanca_excecao_para_modalidade_invalida_no_calculate()
    {
        // Arrange
        $precoOriginal = 10000;
        $porcentagemTaxa = 0.05;
        $modalidadeInvalida = 'modalidade_invalida';

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Modalidade de taxa inválida: modalidade_invalida');

        // Act
        $this->calculator->calculate($precoOriginal, $porcentagemTaxa, $modalidadeInvalida);
    }

    /** @test */
    public function lanca_excecao_para_modalidade_invalida_no_calculate_display_price()
    {
        // Arrange
        $precoOriginal = 10000;
        $porcentagemTaxa = 0.05;
        $modalidadeInvalida = 'modalidade_invalida';

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Modalidade de taxa inválida: modalidade_invalida');

        // Act
        $this->calculator->calculateDisplayPrice($precoOriginal, $porcentagemTaxa, $modalidadeInvalida);
    }

    /** @test */
    public function calcula_corretamente_com_taxa_zero()
    {
        // Arrange: Taxa de 0%
        $precoOriginal = 10000;
        $porcentagemTaxa = 0.0;
        $modalidade = 'couple_pays';

        // Act
        $resultado = $this->calculator->calculate($precoOriginal, $porcentagemTaxa, $modalidade);

        // Assert
        $this->assertEquals(10000, $resultado->displayPrice);
        $this->assertEquals(0, $resultado->feeAmount, 'Taxa deve ser zero');
        $this->assertEquals(10000, $resultado->netAmountCouple, 'Noivos recebem tudo');
        $this->assertEquals(0, $resultado->platformAmount, 'Plataforma não recebe nada');
    }

    /** @test */
    public function calcula_corretamente_com_preco_minimo()
    {
        // Arrange: Preço mínimo de R$ 1,00
        $precoOriginal = 100; // R$ 1,00 em centavos
        $porcentagemTaxa = 0.05;
        $modalidade = 'couple_pays';

        // Act
        $resultado = $this->calculator->calculate($precoOriginal, $porcentagemTaxa, $modalidade);

        // Assert
        $this->assertEquals(100, $resultado->displayPrice);
        $this->assertEquals(5, $resultado->feeAmount, 'Taxa de R$ 0,05');
        $this->assertEquals(95, $resultado->netAmountCouple, 'Noivos recebem R$ 0,95');
    }

    /** @test */
    public function payment_amounts_converte_para_array_corretamente()
    {
        // Arrange
        $paymentAmounts = new PaymentAmounts(
            displayPrice: 10000,
            grossAmount: 10000,
            feeAmount: 500,
            netAmountCouple: 9500,
            platformAmount: 500
        );

        // Act
        $array = $paymentAmounts->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals([
            'display_price' => 10000,
            'gross_amount' => 10000,
            'fee_amount' => 500,
            'net_amount_couple' => 9500,
            'platform_amount' => 500,
        ], $array);
    }

    /** @test */
    public function garante_que_noivos_recebem_valor_original_em_guest_pays()
    {
        // Arrange: Diferentes valores e taxas
        $casos = [
            ['preco' => 10000, 'taxa' => 0.05],
            ['preco' => 25000, 'taxa' => 0.10],
            ['preco' => 50000, 'taxa' => 0.03],
            ['preco' => 100, 'taxa' => 0.05],
        ];

        foreach ($casos as $caso) {
            // Act
            $resultado = $this->calculator->calculate($caso['preco'], $caso['taxa'], 'guest_pays');

            // Assert
            $this->assertEquals(
                $caso['preco'],
                $resultado->netAmountCouple,
                "Noivos devem receber exatamente o valor original de {$caso['preco']} centavos"
            );
        }
    }

    /** @test */
    public function soma_de_valores_esta_correta_em_couple_pays()
    {
        // Arrange
        $precoOriginal = 10000;
        $porcentagemTaxa = 0.05;

        // Act
        $resultado = $this->calculator->calculate($precoOriginal, $porcentagemTaxa, 'couple_pays');

        // Assert: netAmountCouple + platformAmount deve ser igual ao grossAmount
        $this->assertEquals(
            $resultado->grossAmount,
            $resultado->netAmountCouple + $resultado->platformAmount,
            'A soma dos valores dos noivos e da plataforma deve ser igual ao valor bruto'
        );
    }

    /** @test */
    public function soma_de_valores_esta_correta_em_guest_pays()
    {
        // Arrange
        $precoOriginal = 10000;
        $porcentagemTaxa = 0.05;

        // Act
        $resultado = $this->calculator->calculate($precoOriginal, $porcentagemTaxa, 'guest_pays');

        // Assert: netAmountCouple + platformAmount deve ser igual ao grossAmount
        $this->assertEquals(
            $resultado->grossAmount,
            $resultado->netAmountCouple + $resultado->platformAmount,
            'A soma dos valores dos noivos e da plataforma deve ser igual ao valor bruto'
        );
    }
}
