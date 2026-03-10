<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Model;

use DaveLiddament\TestMapper\Model\ChangedSpecFile;
use DaveLiddament\TestMapper\Model\FileChangeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangedSpecFile::class)]
final class ChangedSpecFileTest extends TestCase
{
    #[Test]
    public function itFormatsAddedFile(): void
    {
        $file = new ChangedSpecFile(FileChangeType::Added, 'auth/login.md');
        self::assertSame('[added] auth/login.md', $file->getFormattedOutput());
    }

    #[Test]
    public function itFormatsModifiedFile(): void
    {
        $file = new ChangedSpecFile(FileChangeType::Modified, 'auth/login.md');
        self::assertSame('[modified] auth/login.md', $file->getFormattedOutput());
    }

    #[Test]
    public function itFormatsDeletedFile(): void
    {
        $file = new ChangedSpecFile(FileChangeType::Deleted, 'auth/login.md');
        self::assertSame('[deleted] auth/login.md', $file->getFormattedOutput());
    }
}
