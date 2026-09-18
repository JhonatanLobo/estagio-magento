<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Api\Data;

interface AvaliacaoInterface
{
    public const AVALIACAO_ID = 'avaliacao_id';
    public const PRODUCT_ID = 'product_id';
    public const AUTOR = 'autor';
    public const COMENTARIO = 'comentario';
    public const NOTA = 'nota';
    public const APROVADO = 'aprovado';
    public const CREATED_AT = 'created_at';

    public function getId();

    public function getAvaliacaoId(): ?int;

    public function setAvaliacaoId(int $avaliacaoId): self;

    public function getProductId(): int;

    public function setProductId(int $productId): self;

    public function getAutor(): string;

    public function setAutor(string $autor): self;

    public function getComentario(): ?string;

    public function setComentario(?string $comentario): self;

    public function getNota(): int;

    public function setNota(int $nota): self;

    public function isAprovado(): bool;

    public function setAprovado(bool $aprovado): self;

    public function getCreatedAt(): ?string;

    public function setCreatedAt(string $createdAt): self;
}