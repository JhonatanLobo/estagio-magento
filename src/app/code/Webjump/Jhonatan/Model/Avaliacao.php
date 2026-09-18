<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model;

use Magento\Framework\Model\AbstractModel;
use Webjump\Jhonatan\Api\Data\AvaliacaoInterface;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao as AvaliacaoResourceModel;

class Avaliacao extends AbstractModel implements AvaliacaoInterface
{
    protected function _construct(): void
    {
        $this->_init(AvaliacaoResourceModel::class);
    }

    public function getAvaliacaoId(): ?int
    {
        $id = $this->getData(self::AVALIACAO_ID);
        return $id !== null ? (int) $id : null;
    }

    public function setAvaliacaoId(int $avaliacaoId): self
    {
        return $this->setData(self::AVALIACAO_ID, $avaliacaoId);
    }

    public function getProductId(): int
    {
        return (int) $this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getAutor(): string
    {
        return (string) $this->getData(self::AUTOR);
    }

    public function setAutor(string $autor): self
    {
        return $this->setData(self::AUTOR, $autor);
    }

    public function getComentario(): ?string
    {
        $val = $this->getData(self::COMENTARIO);
        return $val !== null ? (string) $val : null;
    }

    public function setComentario(?string $comentario): self
    {
        return $this->setData(self::COMENTARIO, $comentario);
    }

    public function getNota(): int
    {
        return (int) $this->getData(self::NOTA);
    }

    public function setNota(int $nota): self
    {
        return $this->setData(self::NOTA, $nota);
    }

    public function isAprovado(): bool
    {
        return (bool) $this->getData(self::APROVADO);
    }

    public function setAprovado(bool $aprovado): self
    {
        return $this->setData(self::APROVADO, $aprovado);
    }

    public function getCreatedAt(): ?string
    {
        $val = $this->getData(self::CREATED_AT);
        return $val !== null ? (string) $val : null;
    }

    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}