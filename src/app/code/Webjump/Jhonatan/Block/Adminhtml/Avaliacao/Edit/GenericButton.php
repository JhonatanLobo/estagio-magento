<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Block\Adminhtml\Avaliacao\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\Jhonatan\Api\AvaliacaoRepositoryInterface;

class GenericButton
{
    protected Context $context;
    protected AvaliacaoRepositoryInterface $avaliacaoRepository;

    public function __construct(
        Context $context,
        AvaliacaoRepositoryInterface $avaliacaoRepository
    ) {
        $this->context = $context;
        $this->avaliacaoRepository = $avaliacaoRepository;
    }

    public function getAvaliacaoId(): ?int
    {
        $avaliacaoId = $this->context->getRequest()->getParam('avaliacao_id');
        if (!$avaliacaoId) {
            return null;
        }

        try {
            return (int) $this->avaliacaoRepository->getById((int) $avaliacaoId)->getAvaliacaoId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}