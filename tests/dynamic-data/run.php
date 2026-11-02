<?php
/**
 * Test runner for dynamic data.
 *
 * Plain CLI harness like tests/ai/run.php: boots WordPress, runs every
 * test-*.php in this directory and reports.
 *
 * Usage:  php tests/dynamic-data/run.php [filter]
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'This runner is CLI only.' );
}

final class SD_DD_Test {

	/** @var array<int, array{name: string, ok: bool, detail: string}> */
	public static array $results = array();

	public static string $group = '';

	public static function group( string $name ): void {
		self::$group = $name;
	}

	public static function same( string $name, $expected, $actual ): void {
		$ok              = $expected === $actual;
		self::$results[] = array(
			'name'   => ( self::$group ? self::$group . ': ' : '' ) . $name,
			'ok'     => $ok,
			'detail' => $ok ? '' : 'expected ' . self::show( $expected ) . ', got ' . self::show( $actual ),
		);
	}

	public static function show( $value ): string {
		return str_replace( array( "\n", '  ' ), array( '\n', ' ' ), var_export( $value, true ) );
	}

	public static function report(): int {
		$pass = 0;
		$fail = 0;

		foreach ( self::$results as $result ) {
			if ( $result['ok'] ) {
				++$pass;
				echo 'PASS  ' . $result['name'] . "\n";
				continue;
			}

			++$fail;
			echo 'FAIL  ' . $result['name'] . "\n      " . $result['detail'] . "\n";
		}

		echo "\n" . $pass . ' passed, ' . $fail . " failed\n";

		return $fail > 0 ? 1 : 0;
	}
}

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
	exit( 'Could not locate wp-load.php above ' . __DIR__ . "\n" );
}

$_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once $wp_load;

$filter = $argv[1] ?? '';
$files  = glob( __DIR__ . '/test-*.php' );
sort( $files );

foreach ( $files as $file ) {
	if ( '' !== $filter && false === strpos( basename( $file ), $filter ) ) {
		continue;
	}
	require $file;
}

exit( SD_DD_Test::report() );
