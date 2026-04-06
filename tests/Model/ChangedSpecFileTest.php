<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\FileChangeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangedSpecFile::class)]
#[Ticket('005-changed-spec-file')]
final class ChangedSpecFileTest extends TestCase
{
    #[Test]
    public function itFormatsAddedFile(): void
    {
        $file = new ChangedSpecFile(FileChangeType::Added, 'auth/login');
        self::assertSame('[added] auth/login', $file->getFormattedOutput());
    }

    #[Test]
    public function itFormatsModifiedFile(): void
    {
        $file = new ChangedSpecFile(FileChangeType::Modified, 'auth/login');
        self::assertSame('[modified] auth/login', $file->getFormattedOutput());
    }

    #[Test]
    public function itFormatsDeletedFile(): void
    {
        $file = new ChangedSpecFile(FileChangeType::Deleted, 'auth/login');
        self::assertSame('[deleted] auth/login', $file->getFormattedOutput());
    }
}
