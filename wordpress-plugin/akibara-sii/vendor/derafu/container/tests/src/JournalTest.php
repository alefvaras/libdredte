<?php

declare(strict_types=1);

/**
 * Derafu: Container - Flexible Data Containers for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsContainer;

use Derafu\Container\Journal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Journal::class)]
class JournalTest extends TestCase
{
    private Journal $journal;

    protected function setUp(): void
    {
        $this->journal = new Journal();
    }

    public function testJournalAddAndRetrieveElements(): void
    {
        // Agregar elementos.
        $this->journal->add('first');
        $this->journal->add('second');
        $this->journal->add('third');

        // Verificar orden normal (más antiguo a más nuevo).
        $this->assertSame(
            ['first', 'second', 'third'],
            $this->journal->all()
        );

        // Verificar orden inverso (más nuevo a más antiguo).
        $this->assertSame(
            ['third', 'second', 'first'],
            $this->journal->reverse()
        );
    }

    public function testJournalClearJournal(): void
    {
        $this->journal->add('item');
        $this->journal->clear();

        $this->assertSame([], $this->journal->all());
    }

    public function testJournalJournalWithDifferentTypes(): void
    {
        $object = new stdClass();
        $array = ['key' => 'value'];
        $number = 42;

        $this->journal->add($object);
        $this->journal->add($array);
        $this->journal->add($number);

        $allItems = $this->journal->all();

        $this->assertCount(3, $allItems);
        $this->assertSame($object, $allItems[0]);
        $this->assertSame($array, $allItems[1]);
        $this->assertSame($number, $allItems[2]);
    }
}
