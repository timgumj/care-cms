<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_page_translation_language', columns: ['translationKey','locale'])]
#[UniqueEntity('path')]
#[UniqueEntity(fields: ['translationKey','locale'], ignoreNull: true, message: 'Für diese Übersetzungsgruppe existiert bereits eine Seite in dieser Sprache.')]
class Page
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank, Assert\Regex(pattern: '~^/(?:[a-z0-9-]+/)*$~', message: 'Use an absolute path with a trailing slash.')]
    public string $path = '/';
    #[ORM\Column(length: 80)] public string $kind = 'Page';
    #[ORM\Column(length: 255)] #[Assert\NotBlank, Assert\Length(max: 255)] public string $title = '';
    #[ORM\Column(type: 'text')] public string $description = '';
    #[ORM\Column(type: 'text')] public string $content = '';
    #[ORM\Column(length: 512)] #[Assert\Url] public string $canonical = '';
    // SCHEMA is a reserved MySQL keyword; Doctrine translates the quoting per platform.
    #[ORM\Column(name: '`schema`', type: 'json')] public array $schema = [];
    #[ORM\Column(type: 'text')] public string $bodyClass = '';
    #[ORM\Column(type: 'text')] public string $headExtra = '';
    #[ORM\Column(length: 10, options: ['default'=>'de-AT'])]
    #[Assert\Choice(choices: ['de-AT','en-AT'])] public string $locale = 'de-AT';
    #[ORM\Column(length: 100, nullable: true)] public ?string $translationKey = null;
    #[ORM\Column(type: 'text', options: ['default'=>''])] public string $headerHtml = '';
    #[ORM\Column(type: 'text', options: ['default'=>''])] public string $footerHtml = '';
    #[ORM\Column] public bool $published = true;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] public ?\DateTimeImmutable $deletedAt = null;
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $updatedAt;
    public function __construct() { $this->updatedAt = new \DateTimeImmutable(); }
    public function __toString(): string { return $this->title; }
}
