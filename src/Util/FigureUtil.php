<?php

declare(strict_types=1);

/*
 * This file is part of the Calendar Event Booking Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/calendar-event-booking-bundle
 */

namespace Markocupic\CalendarEventBookingBundle\Util;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\FilesModel;
use Contao\Validator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class FigureUtil
{
    public function __construct(
        #[Autowire(service: 'contao.assets.files_context')]
        private ContaoContext $filesContext,
        private InsertTagParser $insertTagParser,
        #[Autowire(service: 'contao.image.studio')]
        private Studio $studio,
        #[Autowire(service: 'contao.filesystem.virtual.files')]
        private VirtualFilesystem $filesStorage,
        #[Autowire(param: 'contao.image.valid_extensions')]
        private array $validExtensions,
    ) {
    }

    public function buildFigure(array $data = []): Figure|null
    {
        // Find all images (see #5911)
        $filesystemItems = FilesystemUtil::listContentsFromSerialized($this->filesStorage, $data['singleSRC'])
            ->filter(fn ($item) => \in_array($item->getExtension(true), $this->validExtensions, true))
        ;

        $filesystemItem = $filesystemItems->first();

        if (null === $filesystemItem) {
            return null;
        }

        $figureBuilder = $this->studio
            ->createFigureBuilder()
            ->setSize($data['size'] ?? null)
            ->setLightboxGroupIdentifier('lb'.$data['id'] ?? bin2hex(random_bytes(16)))
            ->enableLightbox((bool) ($data['fullsize'] ?? false))
        ;

        $figureBuilder->setOverwriteMetadata($this->getOverwriteMetadata($data));

        return $figureBuilder->fromStorage($this->filesStorage, $filesystemItem->getPath())->buildIfResourceExists();
    }

    public function getOverwriteMetadata(array $data): Metadata|null
    {
        // Ignore if "overwriteMeta" is not set
        if (!$data['overwriteMeta'] ?? false) {
            return null;
        }

        // Normalize keys
        if (isset($data['imageTitle'])) {
            $data[Metadata::VALUE_TITLE] = $data['imageTitle'];
        }

        if (isset($data['imageUrl'])) {
            $url = $data['imageUrl'];

            if (Validator::isRelativeUrl($url)) {
                $url = $this->filesContext->getStaticUrl().$url;
            }

            $data[Metadata::VALUE_URL] = $url;
        }

        unset($data['imageTitle'], $data['imageUrl']);

        // Make sure we resolve insert-tags pointing to files
        if (isset($data[Metadata::VALUE_URL])) {
            $data[Metadata::VALUE_URL] = $this->insertTagParser->replaceInline($data[Metadata::VALUE_URL] ?? '');
        }

        // Strip superfluous fields by intersecting with tl_files.meta.eval.metaFields
        return new Metadata(array_intersect_key($data, array_flip(FilesModel::getMetaFields())));
    }
}
