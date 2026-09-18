<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Webjump\Jhonatan\Api\AvaliacaoRepositoryInterface;
use Webjump\Jhonatan\Model\AvaliacaoFactory;

class AddSampleAvaliacoes implements DataPatchInterface
{
    private AvaliacaoRepositoryInterface $avaliacaoRepository;
    private AvaliacaoFactory $avaliacaoFactory;

    public function __construct(
        AvaliacaoRepositoryInterface $avaliacaoRepository,
        AvaliacaoFactory $avaliacaoFactory
    ) {
        $this->avaliacaoRepository = $avaliacaoRepository;
        $this->avaliacaoFactory = $avaliacaoFactory;
    }

    public function apply(): void
    {
        $sampleReviews = [
            [
                'product_id' => 1,
                'autor' => 'Lucas Ferreira',
                'comentario' => 'Sensor com precisao milimetrica e peso excelente para partidas competitivas.',
                'nota' => 5,
                'aprovado' => true
            ],
            [
                'product_id' => 1,
                'autor' => 'Mariana Silveira',
                'comentario' => 'Bateria durou quase duas semanas de uso continuo no trabalho e nos jogos.',
                'nota' => 5,
                'aprovado' => true
            ],
            [
                'product_id' => 1,
                'autor' => 'Carlos Eduardo',
                'comentario' => 'Construcao solida e clique firme, mas o software poderia ter mais perfis.',
                'nota' => 4,
                'aprovado' => true
            ],
            [
                'product_id' => 1,
                'autor' => 'Beatriz Ramos',
                'comentario' => 'Produto de acabamento premium, ergonomia muito confortavel.',
                'nota' => 5,
                'aprovado' => false
            ],
            [
                'product_id' => 1,
                'autor' => 'Rodrigo Mendes',
                'comentario' => 'Cabo para carregamento flexivel e sem arrasto. Recomendo muito.',
                'nota' => 4,
                'aprovado' => false
            ]
        ];

        foreach ($sampleReviews as $data) {
            $avaliacao = $this->avaliacaoFactory->create();
            $avaliacao->setProductId($data['product_id']);
            $avaliacao->setAutor($data['autor']);
            $avaliacao->setComentario($data['comentario']);
            $avaliacao->setNota($data['nota']);
            $avaliacao->setAprovado($data['aprovado']);
            $this->avaliacaoRepository->save($avaliacao);
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}