<?php

namespace App\Form\Model;

use App\Entity\ForumCategory;
use App\Validator\Constraints\Unique;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique(entityClass="App\Entity\ForumCategory", errorPath="name",
 *     fields={"normalizedName"}, idFields={"entityId": "id"})
 */
final class ForumCategoryData {
    /**
     * @var int|null
     */
    private $entityId;

    /**
     * @Assert\Length(min=3, max=40)
     * @Assert\NotBlank()
     * @Assert\Regex("/^\w+$/")
     *
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $normalizedName;

    /**
     * @Assert\Length(min=1, max=80)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $title;

    /**
     * @Assert\Length(max=300)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $description;

    /**
     * @Assert\Length(max=1500)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $sidebar;

    public function __construct(ForumCategory $forumCategory = null) {
        if ($forumCategory) {
            $this->entityId = $forumCategory->getId();
            $this->setName($forumCategory->getName());
            $this->title = $forumCategory->getTitle();
            $this->description = $forumCategory->getDescription();
            $this->sidebar = $forumCategory->getSidebar();
        }
    }

    public function toForumCategory(): ForumCategory {
        return new ForumCategory(
            $this->name,
            $this->title,
            $this->description,
            $this->sidebar
        );
    }

    public function updateForumCategory(ForumCategory $category): void {
        $category->setName($this->name);
        $category->setTitle($this->title);
        $category->setDescription($this->description);
        $category->setSidebar($this->sidebar);
    }

    public function getEntityId(): ?int {
        return $this->entityId;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name): void {
        $this->name = $name;
        $this->normalizedName = isset($name)
            ? ForumCategory::normalizeName($name)
            : null;
    }

    public function getNormalizedName(): ?string {
        return $this->normalizedName;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(?string $title): void {
        $this->title = $title;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function getSidebar(): ?string {
        return $this->sidebar;
    }

    public function setSidebar(?string $sidebar): void {
        $this->sidebar = $sidebar;
    }
}
