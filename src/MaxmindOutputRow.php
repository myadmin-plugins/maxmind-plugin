<?php
/**
 * Maps a MaxMind minFraud response onto a `maxmind_output` row.
 *
 * Pure: no database handle, no clock. The INSERT lives in maxmind.inc.php; everything
 * that decides WHAT to store is here so it can be tested without a cluster.
 *
 * Background (plan_ccs.md 3.10): `maxmind_output` has existed for years and is
 * commented "MaxMind Response History", but nothing writes it — `update_maxmind()` puts
 * the response inside the account's `ccs` JSON blob instead. Its 88,881 rows are one per
 * ACCOUNT, not one per request, which is what you get when a table is populated once and
 * then abandoned. Reviving it is what lets `account_ccs.account_cc_maxmindid` reference
 * something real, and it is why the fraud response no longer needs to live in a blob.
 *
 * The column mapping is mechanical and verified against the live schema: every one of
 * the 50 documented response fields is that field name lowercased. The table carries
 * three extra columns — `account_id` (ours) and `highriskusername`/`highriskpassword`,
 * both deprecated out of the current API — so an allowlist built from the live column
 * list is the honest way to do this rather than a hand-maintained map that drifts.
 *
 * @author Joe Huss <detain@interserver.net>
 * @package MyAdmin
 * @category Maxmind
 */

namespace Detain\MyAdminMaxMind;

final class MaxmindOutputRow
{
    /**
     * MaxMind's own request id. It is the PRIMARY KEY of `maxmind_output`, so a response
     * without one cannot be stored at all.
     */
    public const ID_FIELD = 'maxmindID';

    /**
     * Build the row to insert.
     *
     * @param int $custid
     * @param array<string,mixed> $response the decoded minFraud response
     * @param array<int,string> $columns the live column list of `maxmind_output`
     * @return array<string,mixed>|null null when the response cannot be keyed
     */
    public static function build(int $custid, array $response, array $columns): ?array
    {
        $id = self::id($response);
        if ($id === null) {
            return null;
        }
        $allowed = array_flip($columns);
        $row = ['maxmindid' => $id, 'account_id' => $custid];
        foreach ($response as $key => $value) {
            $column = strtolower((string)$key);
            if ($column === 'maxmindid' || $column === 'account_id') {
                continue;   // already set, and account_id is never MaxMind's to supply
            }
            if (!isset($allowed[$column])) {
                // A field MaxMind added that this table does not have a column for.
                // Dropping it is correct: the full response is logged either way, and
                // silently widening the row would fail the INSERT instead.
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;   // no column in this table is structured
            }
            $row[$column] = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
        }
        return $row;
    }

    /**
     * The response's MaxMind id, trimmed, or null when it is absent or blank.
     *
     * Every one of the 88,881 existing rows has one — including the 13,626 that carry an
     * `err` — so error responses are storable too. But it is the primary key, so an
     * absent id has to mean "skip", never "insert a blank".
     *
     * @param array<string,mixed> $response
     */
    public static function id(array $response): ?string
    {
        foreach ([self::ID_FIELD, 'maxmindid', 'maxmind_id'] as $key) {
            if (isset($response[$key]) && trim((string)$response[$key]) !== '') {
                return trim((string)$response[$key]);
            }
        }
        return null;
    }

    /**
     * Fields to overwrite when the same maxmindID comes back twice.
     *
     * MaxMind ids are meant to be unique per request, so this should never fire. It
     * exists because the alternative — a duplicate-key error aborting the INSERT — would
     * turn a MaxMind quirk into a failed card lookup.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public static function onDuplicate(array $row): array
    {
        unset($row['maxmindid']);
        return $row;
    }
}
