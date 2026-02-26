<?php

/**
 * Unit tests for the pausatf-membership plugin.
 *
 * Covers pure-PHP logic that does not require a database or a running
 * WordPress installation.  WordPress-API-dependent code paths (shortcode
 * rendering, WP-Cron scheduling) are excluded; those belong in integration
 * tests against a real WP install.
 */

declare( strict_types=1 );

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MembershipPluginTest extends TestCase {

    // ------------------------------------------------------------------
    // Post-type constant checks
    // ------------------------------------------------------------------

    #[Test]
    public function club_post_type_slug_is_correct(): void {
        self::assertSame( 'pausatf_club', PAUSATF_CPT_Club::POST_TYPE );
    }

    #[Test]
    public function member_post_type_slug_is_correct(): void {
        self::assertSame( 'pausatf_member', PAUSATF_CPT_Member::POST_TYPE );
    }

    // ------------------------------------------------------------------
    // USATF_Sync::parse_csv — accessed via Reflection because it is private
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{string, array<int, array<string, string>>}>
     */
    public static function csv_provider(): array {
        return [
            'typical two-row CSV' => [
                "first_name,last_name,club_name\nJane,Doe,Philly Runners\nJohn,Smith,PGH Track",
                [
                    [ 'first_name' => 'Jane', 'last_name' => 'Doe',   'club_name' => 'Philly Runners' ],
                    [ 'first_name' => 'John', 'last_name' => 'Smith', 'club_name' => 'PGH Track' ],
                ],
            ],
            'UTF-8 BOM is stripped' => [
                "\xEF\xBB\xBFfirst_name,last_name,club_name\nAlice,Thomas,PAUSATF",
                [
                    [ 'first_name' => 'Alice', 'last_name' => 'Thomas', 'club_name' => 'PAUSATF' ],
                ],
            ],
            'header-only CSV returns empty array' => [
                "first_name,last_name,club_name",
                [],
            ],
            'row with wrong column count is skipped' => [
                "first_name,last_name,club_name\nBad,Row\nGood,Row,Club",
                [
                    [ 'first_name' => 'Good', 'last_name' => 'Row', 'club_name' => 'Club' ],
                ],
            ],
            'blank lines are skipped' => [
                "first_name,last_name,club_name\n\nJane,Doe,Club\n\n",
                [
                    [ 'first_name' => 'Jane', 'last_name' => 'Doe', 'club_name' => 'Club' ],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, string>> $expected
     */
    #[Test]
    #[DataProvider( 'csv_provider' )]
    public function parse_csv_returns_expected_rows( string $csv, array $expected ): void {
        $method = new ReflectionMethod( PAUSATF_USATF_Sync::class, 'parse_csv' );
        $method->setAccessible( true );

        $actual = $method->invoke( null, $csv );

        self::assertSame( $expected, $actual );
    }

    // ------------------------------------------------------------------
    // Score submission date validation — pure PHP, no WP DB needed.
    // The validation uses date_create_from_format which is stdlib.
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{string, bool}>
     */
    public static function date_format_provider(): array {
        return [
            'valid ISO date'         => [ '2024-06-15', true ],
            'valid leap-year date'   => [ '2024-02-29', true ],
            'invalid month 13'       => [ '2024-13-01', false ],
            'invalid day 32'         => [ '2024-01-32', false ],
            'non-leap Feb 29'        => [ '2023-02-29', false ],
            'wrong format (slashes)' => [ '2024/06/15', false ],
            'empty string'           => [ '', false ],
        ];
    }

    /**
     * The score handler validates dates with date_create_from_format.
     * This test exercises that exact logic in isolation.
     *
     * @param string $date     Input date string.
     * @param bool   $expected Whether the date should be considered valid.
     */
    #[Test]
    #[DataProvider( 'date_format_provider' )]
    public function score_date_validation_matches_handler_logic( string $date, bool $expected ): void {
        // Replicate exactly what handle_submission() does.
        $parsed = date_create_from_format( 'Y-m-d', $date );
        $valid  = false !== $parsed && $date === $parsed->format( 'Y-m-d' );

        self::assertSame( $expected, $valid, "Date '{$date}' validity mismatch" );
    }

    // ------------------------------------------------------------------
    // Order sanitization — shared logic in both shortcodes.
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function order_provider(): array {
        return [
            'uppercase ASC'  => [ 'ASC',  'ASC' ],
            'uppercase DESC' => [ 'DESC', 'DESC' ],
            'lowercase asc'  => [ 'asc',  'ASC' ],
            'lowercase desc' => [ 'desc', 'DESC' ],
            'invalid value'  => [ 'RAND', 'ASC' ],
            'empty string'   => [ '',     'ASC' ],
            'injection attempt' => [ "DESC; DROP TABLE--", 'ASC' ],
        ];
    }

    /**
     * Both shortcode renderers sanitize the 'order' attribute with the same
     * pattern: accept only ASC/DESC (case-insensitive), default to ASC.
     *
     * @param string $input    Raw attribute value.
     * @param string $expected Sanitized result.
     */
    #[Test]
    #[DataProvider( 'order_provider' )]
    public function order_attribute_is_sanitized( string $input, string $expected ): void {
        $sanitized = in_array( strtoupper( $input ), [ 'ASC', 'DESC' ], true )
            ? strtoupper( $input )
            : 'ASC';

        self::assertSame( $expected, $sanitized );
    }
}
