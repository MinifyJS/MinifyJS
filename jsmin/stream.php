<?php
class Stream {
	public static function legalStart($t) {
		return preg_match('~\A[\\\\$_\pL\pN\p{Mn}\p{Mc}\p{Pc}]~u', $t) === 1 ? ' ' . $t : $t;
	}

	public static function legalEnd($t) {
		return preg_match('~[\\\\$_\pL\pN\p{Mn}\p{Mc}\p{Pc}]\z~u', $t) === 1 ? $t . ' ' : $t;
	}

	public static function trimSemicolon($t) {
		if (AST::$options['beautify']) {
			return $t;
		}

		return preg_replace('~(?|(;\s*\x00)|;(\s*\n?))+\z~', '$1', $t);
	}

	public static function indent($o) {
		return preg_replace('~^~m', '    ', $o);
	}

	public static function unindent($o) {
		return preg_replace('~^    ~m', '', $o);
	}
}