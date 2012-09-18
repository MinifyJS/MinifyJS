<?php
class String extends ConstantExpression {
	public function __construct($value, $isRaw = true) {
		if ($isRaw) {
			$value = $this->unescape($value);
		}

		parent::__construct($value);
	}

	public function type() {
		return 'string';
	}

	public function value() {
		return $this->left;
	}

	public function asString() {
		return $this->left;
	}

	public function asNumber() {
		return is_numeric($this->left) || !$this->left ? (float)$this->left : NAN;
	}

	public function toString() {
		return AST::bestOption(array($this->quote("'"), $this->quote('"')));
	}

	public function asBoolean() {
		return !!strlen($this->left);
	}

	public function removeUseless() {
		return new VoidExpression(new Number(0));
	}

	protected function quote($c) {
	    $escape = '~\\\\|[' . $c . '\x00-\x1f' . (AST::$options['ascii'] ? '\x7f-\x{ffff}' : '') . '](?=([\s\S]|$))~u';

		return $c . preg_replace_callback($escape, array($this, 'escapeHelper'), $this->value()) . $c;
	}

	protected function escapeHelper($m) {
		$meta = array(
			"\0"   => '\0',
	        "\x08" => '\b',
	        "\t"   => "\t",
	        "\n"   => '\n',
	        "\x0C" => '\f',
	        "\r"   => '\r',
	        '\\'   => '\\\\',
	        "'"    => "\'",
			'"'    => '\"',
			'/'    => '\/'
	 	);

	 	$c = $m[0];

	 	if ($c === "\0" && ctype_digit($m[1]) && $m[1] >= 0 && $m[1] < 8) {
	 		return '\u0000';
	 	}

	 	if (isset($meta[$c])) {
	 		return $meta[$c];
	 	}

	 	$x = '0000';
	 	foreach($this->toCodePoints($c) as $cp) {
	 		$x .= base_convert($cp, 10, 16);
	 	}

	 	return '\u' . substr($x, -4);
	}

	protected function unescape($m) {
		if (is_array($m)) {
			if (isset($m[3])) {
				switch($m[3]) {
				case 't':  return "\t";
				case 'n':  return "\n";
				case 'r':  return "\r";
				case 'b':  return "\x08";
				case 'f':  return "\x0C";
				case "\n": return '';
				default:   return $m[3];
				}
			}

			if (isset($m[2])) {
				return "\0";
			}

			$cp = (int)base_convert($m[1], 16, 10);

	        if($cp < 0x80) {
	        	$returnStr = chr($cp);
	       	} elseif($cp < 0x800) {
	       		$returnStr = chr(0xC0 | $cp >> 6) .
					chr(0x80 | ($cp & 0x3F));
			} elseif($cp < 0x10000) {
	        	$returnStr = chr(0xE0 | $cp >> 12) .
					chr(0x80 | ($cp >> 6 & 0x3F)) .
					chr(0x80 | $cp & 0x3F);
			} else {
				$returnStr = chr(0xF0 | $cp >> 18) .
					chr(0x80 | ($cp >> 12 & 0x3F)) .
					chr(0x80 | ($cp >> 6 & 0x3F)) .
					chr(0x80 | $cp & 0x3F);
			}

			return $returnStr;
		}

		$c = $m[0];
		if ($c === substr($m, -1)) {
			return preg_replace_callback('~\\\\(?:(?|u([\da-f]{4})|x([\da-f]{2}))|(0)(?!\d)|(.))~si', array($this, 'unescape'), substr($m, 1, -1));
		} return $m;
	}

	protected function toCodePoints($string) {
		if (is_string($string)) {
			$unicodePoints = array();
			$strlen = strlen($string);
			$pos = 0;
			$codePoint = 0;

			while ($pos < $strlen){
				$length = 0;
				$char = ord($string[$pos++]);
				if (!($char & 0x80)) {
					$codePoint = $char;
				} elseif (0xC0 === ($char & 0xE0)) {
					$length = 1;
					$codePoint = $char & 0x1F;
				} elseif (0xE0 === ($char & 0xF0)) {
					$length = 2;
					$codePoint = $char & 0xF;
				} elseif (0xF0 === ($char & 0xF8)) {
					$length = 3;
					$codePoint = $char & 0x7;
				} else {
					return null;
				}

				if ($pos + $length > $strlen) {
					return null;
				}

				while ($length--) {
					$char = ord($string[$pos++]);
					if (($char & 0xC0) !== 0x80) {
						continue 2;
					}

					$codePoint = $codePoint << 6 | $char & 0x3F;
				}

				$unicodePoints[] = $codePoint;
			}

			return $unicodePoints;
		}

		return null;
	}

	public function countLetters(&$letters) {
		$this->counter($this->toString(), $letters);
	}

	public function isImmutable() {
		return true;
	}

	public function debug() {
		return $this->toString();
	}
}
