<?php

namespace Jikan\Parser\Manga;

use Jikan\Helper\JString;
use Jikan\Helper\Parser;
use Jikan\Model\DateRange;
use Jikan\Model\Manga;
use Jikan\Parser\Common\MalUrlParser;
use Jikan\Parser\ParserInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class MangaParser
 *
 * @package Jikan\Parser
 */
class MangaParser implements ParserInterface
{
    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * MangaParser constructor.
     *
     * @param Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * @return Manga
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getModel(): Manga
    {
        return Manga::fromParser($this);
    }

    /**
     * @return int
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getMangaId(): int
    {
        preg_match('#https?://myanimelist.net/manga/(\d+)#', $this->getMangaURL(), $matches);

        return (int)$matches[1];
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getMangaURL(): string
    {
        return $this->crawler->filterXPath('//meta[@property=\'og:url\']')->attr('content');
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getMangaTitle(): string
    {
        return $this->crawler->filterXPath('//meta[@property=\'og:title\']')->attr('content');
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getMangaImageURL(): string
    {
        return $this->crawler->filterXPath('//meta[@property=\'og:image\']')->attr('content');
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getMangaSynopsis(): string
    {
        return JString::cleanse(
            $this->crawler->filterXPath('//meta[@property=\'og:description\']')->attr('content')
        );
    }

    /**
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function getMangaTitleEnglish(): ?string
    {
        $title = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="English:"]');
        if (!$title->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace($title->text(), '', $title->parents()->text())
        );
    }

    /**
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function getMangaTitleSynonyms(): ?string
    {
        $title = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Synonyms:"]');
        if (!$title->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace($title->text(), '', $title->parents()->text())
        );
    }

    /**
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function getMangaTitleJapanese(): ?string
    {
        $title = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Japanese:"]');
        if (!$title->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace($title->text(), '', $title->parents()->text())
        );
    }

    /**
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function getMangaType(): ?string
    {
        $type = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Type:"]');
        if (!$type->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace($type->text(), '', $type->parents()->text())
        );
    }

    /**
     * @return int|null
     * @throws \InvalidArgumentException
     */
    public function getMangaChapters(): ?int
    {
        $chapters = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Chapters:"]');

        if (!$chapters->count()) {
            return null;
        }

        return (str_replace($chapters->text(), '', $chapters->parents()->text()) === 'Unknown') ? 0 : (int)str_replace(
            $chapters->text(),
            '',
            $chapters->parents()->text()
        );
    }

    /**
     * @return int|null
     * @throws \InvalidArgumentException
     */
    public function getMangaVolumes(): ?int
    {
        $chapters = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Volumes:"]');

        if (!$chapters->count()) {
            return null;
        }

        return (str_replace($chapters->text(), '', $chapters->parents()->text()) === 'Unknown') ? 0 : (int)str_replace(
            $chapters->text(),
            '',
            $chapters->parents()->text()
        );
    }

    /**
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function getMangaStatus(): ?string
    {
        $status = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Status:"]');
        if (!$status->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace($status->text(), '', $status->parents()->text())
        );
    }

    /**
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getMangaAuthors(): array
    {
        $author = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Authors:"]');

        if ($author->count() && strpos($author->parents()->text(), 'None found') === false) {
            return $author->parents()->first()->filter('a')->each(
                function (Crawler $crawler) {
                    return (new MalUrlParser($crawler))->getModel();
                }
            );
        }

        return []; // If `None found`
    }

    /**
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getMangaSerialization(): array
    {
        $studio = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Serialization:"]');

        if ($studio->count() && strpos($studio->parents()->text(), 'None found') === false) {
            return $studio->parents()->first()->filter('a')->each(
                function (Crawler $crawler) {
                    return (new MalUrlParser($crawler))->getModel();
                }
            );
        }

        return []; // If `None found`
    }

    /**
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getMangaGenre(): array
    {
        $genre = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Genres:"]');

        if ($genre->count() && strpos($genre->parents()->text(), 'No genres have been added yet') === false) {
            return $genre->parents()->first()->filter('a')->each(
                function (Crawler $crawler) {
                    return (new MalUrlParser($crawler))->getModel();
                }
            );
        }

        return []; // If `No genres have been added yet`
    }

    /**
     * @return float
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getMangaScore(): ?float
    {
        $score = $this->crawler
            ->filter('span[itemprop="ratingValue"]');

        if (!$score->count()) {
            return null;
        }

        if (strpos($score->text(), 'N/A')) {
            return null;
        }

        return (float)$score->text();
    }

    /**
     * @return int
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getMangaScoredBy(): ?int
    {
        $scoredBy = $this->crawler
            ->filter('span[itemprop="ratingCount"]');

        if (!$scoredBy->count()) {
            return null;
        }

        return (int)$scoredBy->text();
    }

    /**
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getMangaRank(): ?int
    {
        $rank = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Ranked:"]');

        if (!$rank->count()) {
            return null;
        }

        $rank = Parser::removeChildNodes($rank->parents());
        $ranked = trim(
            str_replace(
                '#',
                '',
                $rank->text()
            )
        );

        return $ranked !== 'N/A' ? (int)$ranked : null;
    }

    /**
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getMangaPopularity(): ?int
    {
        $popularity = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Popularity:"]');

        if (!$popularity->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace([$popularity->text(), '#'], '', $popularity->parents()->text())
        );
    }

    /**
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getMangaMembers(): ?int
    {
        $member = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Members:"]');

        if (!$member->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace([$member->text(), ','], '', $member->parents()->text())
        );
    }

    /**
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getMangaFavorites(): ?int
    {
        $favorite = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Favorites:"]');

        if (!$favorite->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace([$favorite->text(), ','], '', $favorite->parents()->text())
        );
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getMangaRelated(): array
    {
        return $this->crawler
            ->filterXPath('//table[contains(@class, "anime_detail_related_anime")]/tr/td/a')
            ->each(
                function (Crawler $c) {
                    return (new MalUrlParser($c))->getModel();
                }
            );
    }

    /**
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getMangaBackground(): ?string
    {
        $background = Parser::removeChildNodes($this->crawler->filterXPath('//span[@itemprop="description"]/..'));
        $background = $background->text();
        if (preg_match('~No background information has been added to this title~', $background)) {
            return null;
        }

        return JString::cleanse($background);
    }

    /**
     * @return DateRange
     * @throws \InvalidArgumentException
     */
    public function getPublished(): DateRange
    {
        return new DateRange($this->getMangaPublishedString());
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getMangaPublishedString(): ?string
    {
        $aired = $this->crawler
            ->filterXPath('//div[@id="content"]/table/tr/td[@class="borderClass"]')
            ->filterXPath('//span[text()="Published:"]');

        if (!$aired->count()) {
            return null;
        }

        return JString::cleanse(
            str_replace($aired->text(), '', $aired->parents()->text())
        );
    }
}
