<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class HomeBlock implements ArgumentInterface
{
    public function getSectionTitle(): string
    {
        return 'Compromissos de Entrega e Atendimento';
    }

    public function getSectionSubtitle(): string
    {
        return 'Padrões de serviço aplicados diretamente à sua experiência de compra';
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getBenefits(): array
    {
        return [
            [
                'id' => 'shipping',
                'title' => 'Logística Integrada',
                'description' => 'Despacho prioritário com rastreamento ponta a ponta e seguro de carga incluso.',
                'tag' => 'Nacional',
                'svg_path' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12'
            ],
            [
                'id' => 'warranty',
                'title' => 'Garantia Homologada',
                'description' => 'Procedência fiscal assegurada com cobertura técnica direta do fabricante.',
                'tag' => '12 Meses',
                'svg_path' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'
            ],
            [
                'id' => 'security',
                'title' => 'Transação Criptografada',
                'description' => 'Processamento de pagamentos sob conformidade PCI-DSS e tokenização bancária.',
                'tag' => 'SSL 256-bit',
                'svg_path' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z'
            ],
            [
                'id' => 'support',
                'title' => 'Suporte Especializado',
                'description' => 'Canal de atendimento corporativo para esclarecimento técnico de produtos.',
                'tag' => 'Humano',
                'svg_path' => 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z'
            ]
        ];
    }

    public function hasBenefits(): bool
    {
        return !empty($this->getBenefits());
    }
}