<?php
/**
 * Test runner for the AI schema API.
 *
 * There is no PHPUnit in this package, so this is a plain CLI harness: it boots
 * WordPress, loads every test-*.php in this directory, and reports. Tests run
 * against the blocks actually registered on this install, which is the point —
 * the transformer's job is to survive real registration data, not fixtures.
 *
 * Usage:  php tests/ai/run.php [filter]
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'This runner is CLI only.' );
}

// ---------------------------------------------------------------------------
// Assertion helpers
// ---------------------------------------------------------------------------

final class SD_Ai_Test {

	/** @var array<int, array{name: string, ok: bool, detail: string}> */
	public static array $results = array();

	/** @var string */
	public static string $group = '';

	public static function group( string $name ): void {
		self::$group = $name;
	}

	public static function ok( string $name, bool $condition, string $detail = '' ): void {
		self::$results[] = array(
			'name'   => ( self::$group ? self::$group . ': ' : '' ) . $name,
			'ok'     => $condition,
			'detail' => $detail,
		);
	}

	public static function same( string $name, $expected, $actual ): void {
		$ok = $expected === $actual;
		self::ok(
			$name,
			$ok,
			$ok ? '' : 'expected ' . self::show( $expected ) . ', got ' . self::show( $actual )
		);
	}

	/** Report a measurement without passing or failing on it. */
	public static function note( string $line ): void {
		self::$results[] = array( 'name' => $line, 'ok' => null, 'detail' => '' );
	}

	public static function show( $value ): string {
		return str_replace( array( "\n", '  ' ), array( '', ' ' ), var_export( $value, true ) );
	}

	public static function report(): int {
		$pass = 0;
		$fail = 0;

		foreach ( self::$results as $result ) {
			if ( null === $result['ok'] ) {
				echo "      " . $result['name'] . "\n";
				continue;
			}

			if ( $result['ok'] ) {
				++$pass;
				echo "PASS  " . $result['name'] . "\n";
				continue;
			}

			++$fail;
			echo "FAIL  " . $result['name'] . "\n";
			if ( '' !== $result['detail'] ) {
				echo "      " . $result['detail'] . "\n";
			}
		}

		echo "\n" . $pass . " passed, " . $fail . " failed\n";

		return $fail > 0 ? 1 : 0;
	}
}

// ---------------------------------------------------------------------------
// Bootstrap WordPress
// ---------------------------------------------------------------------------

$wp_load = '';
$dir     = __DIR__;

while ( '/' !== $dir && '' !== $dir ) {
	if ( file_exists( $dir . '/wp-load.php' ) ) {
		$wp_load = $dir . '/wp-load.php';
		break;
	}
	$dir = dirname( $dir );
}

if ( '' === $wp_load ) {
	exit( "Could not locate wp-load.php above " . __DIR__ . "\n" );
}

// WordPress expects these in a web context; harmless defaults for CLI.
$_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once $wp_load;

// Registry::boot() runs on widgets_init, which a CLI load does not reach.
if ( ! did_action( 'widgets_init' ) ) {
	do_action( 'widgets_init' );
}

// ---------------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------------

$filter = $argv[1] ?? '';
$files  = glob( __DIR__ . '/test-*.php' );
sort( $files );

foreach ( $files as $file ) {
	if ( '' !== $filter && false === strpos( basename( $file ), $filter ) ) {
		continue;
	}
	require $file;
}

exit( SD_Ai_Test::report() );
