<?php

namespace Detain\MyAdminMaxMind\Tests;

use Detain\MyAdminMaxMind\MaxmindOutputRow;
use PHPUnit\Framework\TestCase;

/**
 * plan_ccs.md 3.10 — mapping a minFraud response onto a maxmind_output row.
 *
 * The table has existed for years and nothing wrote it; its 88,881 rows are one per
 * ACCOUNT rather than one per request, which is what a table populated once and then
 * abandoned looks like. Reviving it is what lets account_ccs.account_cc_maxmindid point
 * at something real.
 */
class MaxmindOutputRowTest extends TestCase
{
    /** The live column list, as SHOW COLUMNS returns it. */
    private function columns(): array
    {
        return [
            'maxmindid', 'account_id', 'riskscore', 'score', 'explanation', 'countrymatch',
            'highriskcountry', 'distance', 'ip_accuracyradius', 'ip_city', 'ip_region',
            'anonymousproxy', 'proxyscore', 'freemail', 'carderemail', 'queriesremaining',
            'minfraud_version', 'service_level', 'err',
            // deprecated out of the current API but still present on the table
            'highriskusername', 'highriskpassword',
        ];
    }

    public function testMapsResponseKeysOntoLowercasedColumns(): void
    {
        $row = MaxmindOutputRow::build(1234, [
            'maxmindID' => 'ABCD1234',
            'riskScore' => '12.34',
            'countryMatch' => 'Yes',
            'ip_accuracyRadius' => 5,
        ], $this->columns());

        $this->assertSame('ABCD1234', $row['maxmindid']);
        $this->assertSame(1234, $row['account_id']);
        $this->assertSame('12.34', $row['riskscore']);
        $this->assertSame('Yes', $row['countrymatch']);
        $this->assertSame(5, $row['ip_accuracyradius']);
    }

    public function testRefusesAResponseWithNoMaxmindId(): void
    {
        // maxmindid is the PRIMARY KEY, so an absent id has to mean "skip" —
        // never "insert a blank", which would collide on the second such response.
        $this->assertNull(MaxmindOutputRow::build(1, ['riskScore' => '1'], $this->columns()));
        $this->assertNull(MaxmindOutputRow::build(1, ['maxmindID' => ''], $this->columns()));
        $this->assertNull(MaxmindOutputRow::build(1, ['maxmindID' => '   '], $this->columns()));
    }

    public function testAnErrorResponseIsStillStored(): void
    {
        // 13,626 of the existing rows carry an `err` and all of them have an id, so an
        // error response is history worth keeping, not something to drop.
        $row = MaxmindOutputRow::build(7, [
            'maxmindID' => 'ERR00001',
            'err' => 'IP_NOT_FOUND',
        ], $this->columns());
        $this->assertSame('IP_NOT_FOUND', $row['err']);
    }

    public function testDropsFieldsTheTableHasNoColumnFor(): void
    {
        // A field MaxMind adds later must not fail the INSERT. The full response is
        // logged either way.
        $row = MaxmindOutputRow::build(1, [
            'maxmindID' => 'ABCD1234',
            'someBrandNewField' => 'x',
        ], $this->columns());
        $this->assertArrayNotHasKey('somebrandnewfield', $row);
        $this->assertArrayHasKey('maxmindid', $row);
    }

    public function testDropsStructuredValues(): void
    {
        // No column on this table is structured; a nested array would fail the INSERT.
        $row = MaxmindOutputRow::build(1, [
            'maxmindID' => 'ABCD1234',
            'riskscore' => ['nested'],
        ], $this->columns());
        $this->assertArrayNotHasKey('riskscore', $row);
    }

    public function testNeverLetsTheResponseSupplyAccountId(): void
    {
        // account_id is ours and is FK-constrained to accounts; MaxMind must not be able
        // to point a row at a different customer.
        $row = MaxmindOutputRow::build(1234, [
            'maxmindID' => 'ABCD1234',
            'account_id' => 9999,
        ], $this->columns());
        $this->assertSame(1234, $row['account_id']);
    }

    public function testNormalizesBooleansToTheEnumWording(): void
    {
        // The enum columns are ('','No','Yes'); a raw PHP true would store as '1'.
        $row = MaxmindOutputRow::build(1, [
            'maxmindID' => 'ABCD1234',
            'freemail' => true,
            'carderemail' => false,
        ], $this->columns());
        $this->assertSame('Yes', $row['freemail']);
        $this->assertSame('No', $row['carderemail']);
    }

    public function testIdAcceptsTheCasingVariants(): void
    {
        $this->assertSame('X1', MaxmindOutputRow::id(['maxmindID' => 'X1']));
        $this->assertSame('X2', MaxmindOutputRow::id(['maxmindid' => 'X2']));
        $this->assertSame('X3', MaxmindOutputRow::id(['maxmind_id' => ' X3 ']));
        $this->assertNull(MaxmindOutputRow::id([]));
    }

    public function testOnDuplicateNeverRewritesThePrimaryKey(): void
    {
        $row = MaxmindOutputRow::build(1, ['maxmindID' => 'ABCD1234', 'riskScore' => '9'], $this->columns());
        $dup = MaxmindOutputRow::onDuplicate($row);
        $this->assertArrayNotHasKey('maxmindid', $dup);
        $this->assertSame('9', $dup['riskscore']);
    }
}
