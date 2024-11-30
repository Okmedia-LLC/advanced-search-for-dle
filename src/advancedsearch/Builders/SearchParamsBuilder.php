<?php

class SearchParamsBuilder {
    
    private $params = [
        'id' => -1,
        'keywords' => '',
        'searchin' => '',
        'extrafields' => [],
        'extrafieldMatch' => 'some',
        'categories' => [],
        'subcats' => false,
        'sort' => 'relasedate',
        'order' => 'asc',
        'relasedate' => '',
        'relasedateDir' => ''
    ];
    
    public function setId(int $id): self {
        $this->params['id'] = $id;
        return $this;
    }

    public function setKeywords(string $keywords): self {
        $this->params['keywords'] = $keywords;
        return $this;
    }

    public function setSearchIn(string $searchin): self {
        $this->params['searchin'] = $searchin;
        return $this;
    }

    public function setExtraFields(array $extrafields): self {
        $this->params['extrafields'] = $extrafields;
        return $this;
    }

    public function setExtraFieldMatch(string $extrafieldMatch): self {
        $this->params['extrafieldMatch'] = $extrafieldMatch;
        return $this;
    }

    public function setCategories(array $categories): self {
        $this->params['categories'] = $categories;
        return $this;
    }

    public function setSubcats(bool $subcats): self {
        $this->params['subcats'] = $subcats;
        return $this;
    }

    public function setSort(string $sort): self {
        $this->params['sort'] = $sort;
        return $this;
    }

    public function setOrder(string $order): self {
        $this->params['order'] = $order;
        return $this;
    }

    public function setReleaseDate(string $relasedate): self {
        $this->params['relasedate'] = $relasedate;
        return $this;
    }

    public function setReleaseDateDir(string $relasedateDir): self {
        $this->params['relasedateDir'] = $relasedateDir;
        return $this;
    }

    public function build(): array {
        return $this->params;
    }
}
