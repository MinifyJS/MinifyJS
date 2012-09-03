<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is the Narcissus JavaScript engine.
 *
 * The Initial Developer of the Original Code is
 * Brendan Eich <brendan@mozilla.org>.
 * Portions created by the Initial Developer are Copyright (C) 2004
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s): Tino Zijdel <crisp@tweakers.net>
 * PHP port, modifications and minifier routine are (C) 2009-2011
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('OP_SEMICOLON', ';');
define('OP_COMMA', ',');
define('OP_HOOK', '?');
define('OP_COLON', ':');
define('OP_OR', '||');
define('OP_AND', '&&');
define('OP_BITWISE_OR', '|');
define('OP_BITWISE_XOR', '^');
define('OP_BITWISE_AND', '&');
define('OP_STRICT_EQ', '===');
define('OP_EQ', '==');
define('OP_ASSIGN', '=');
define('OP_STRICT_NE', '!==');
define('OP_NE', '!=');
define('OP_LSH', '<<');
define('OP_LE', '<=');
define('OP_LT', '<');
define('OP_URSH', '>>>');
define('OP_RSH', '>>');
define('OP_GE', '>=');
define('OP_GT', '>');
define('OP_INCREMENT', '++');
define('OP_DECREMENT', '--');
define('OP_PLUS', '+');
define('OP_MINUS', '-');
define('OP_MUL', '*');
define('OP_DIV', '/');
define('OP_MOD', '%');
define('OP_NOT', '!');
define('OP_BITWISE_NOT', '~');
define('OP_DOT', '.');
define('OP_LEFT_BRACKET', '[');
define('OP_RIGHT_BRACKET', ']');
define('OP_LEFT_CURLY', '{');
define('OP_RIGHT_CURLY', '}');
define('OP_LEFT_PAREN', '(');
define('OP_RIGHT_PAREN', ')');
define('OP_CONDCOMMENT_END', '@*/');
define('OP_UNARY_PLUS', 'U+');
define('OP_UNARY_MINUS', 'U-');

define('KEYWORD_BREAK', 'break');
define('KEYWORD_CASE', 'case');
define('KEYWORD_CATCH', 'catch');
define('KEYWORD_CONTINUE', 'continue');
define('KEYWORD_DEBUGGER', 'debugger');
define('KEYWORD_DEFAULT', 'default');
define('KEYWORD_DELETE', 'delete');
define('KEYWORD_DO', 'do');
define('KEYWORD_ELSE', 'else');
define('KEYWORD_ENUM', 'enum');
define('KEYWORD_FALSE', 'false');
define('KEYWORD_FINALLY', 'finally');
define('KEYWORD_FOR', 'for');
define('KEYWORD_FUNCTION', 'function');
define('KEYWORD_IF', 'if');
define('KEYWORD_IMPLEMENTS', 'implements');
define('KEYWORD_IN', 'in');
define('KEYWORD_INSTANCEOF', 'instanceof');
define('KEYWORD_INTERFACE', 'interface');
define('KEYWORD_LET', 'let');
define('KEYWORD_NEW', 'new');
define('KEYWORD_NULL', 'null');
define('KEYWORD_PACKAGE', 'package');
define('KEYWORD_PRIVATE', 'private');
define('KEYWORD_PROTECTED', 'protected');
define('KEYWORD_PUBLIC', 'public');
define('KEYWORD_RETURN', 'return');
define('KEYWORD_STATIC', 'static');
define('KEYWORD_SWITCH', 'switch');
define('KEYWORD_THIS', 'this');
define('KEYWORD_THROW', 'throw');
define('KEYWORD_TRUE', 'true');
define('KEYWORD_TRY', 'try');
define('KEYWORD_TYPEOF', 'typeof');
define('KEYWORD_VAR', 'var');
define('KEYWORD_VOID', 'void');
define('KEYWORD_WHILE', 'while');
define('KEYWORD_WITH', 'with');
define('KEYWORD_YIELD', 'yield');

class JSTokenizer {
	private $cursor = 0;
	private $source;
	private $chars;
	private $points;
	private $length;

	public $tokens = array();
	public $tokenIndex = 0;
	public $lookahead = 0;
	public $scanNewlines = false;
	public $scanOperand = true;

	public $filename;
	public $lineno;

	public $licenses;

	private $keywords = array(
		'break',
		'case', 'catch', 'continue',
		'debugger', 'default', 'delete', 'do',
		'else', 'enum',
		'false', 'finally', 'for', 'function',
		'if', 'implements', 'in', 'instanceof', 'interface',
		'let',
		'new', 'null',
		'package', 'private', 'protected', 'public',
		'return',
		'static', 'switch',
		'this', 'throw', 'true', 'try', 'typeof',
		'var', 'void',
		'while', 'with',
		'yield'
	);

	private $opTypeNames = array(
		';' => 'SEMICOLON',
		',' => 'COMMA',
		'?' => 'HOOK',
		':' => 'COLON',
		'||' => 'OR',
		'&&' => 'AND',
		'|' => 'BITWISE_OR',
		'^' => 'BITWISE_XOR',
		'&' => 'BITWISE_AND',
		'===' => 'STRICT_EQ',
		'==' => 'EQ',
		'=' => 'ASSIGN',
		'!==' => 'STRICT_NE',
		'!=' => 'NE',
		'<<' => 'LSH',
		'<=' => 'LE',
		'<' => 'LT',
		'>>>' => 'URSH',
		'>>' => 'RSH',
		'>=' => 'GE',
		'>' => 'GT',
		'++' => 'INCREMENT',
		'--' => 'DECREMENT',
		'+' => 'PLUS',
		'-' => 'MINUS',
		'*' => 'MUL',
		'/' => 'DIV',
		'%' => 'MOD',
		'!' => 'NOT',
		'~' => 'BITWISE_NOT',
		'.' => 'DOT',
		'[' => 'LEFT_BRACKET',
		']' => 'RIGHT_BRACKET',
		'{' => 'LEFT_CURLY',
		'}' => 'RIGHT_CURLY',
		'(' => 'LEFT_PAREN',
		')' => 'RIGHT_PAREN',
		'@*/' => 'CONDCOMMENT_END'
	);

	private $assignOps = array('|', '^', '&', '<<', '>>', '>>>', '+', '-', '*', '/', '%');
	private $opRegExp;

	public function __construct($unicodeWS = false) {
		$this->opRegExp = '#\A(' . implode('|', array_map('preg_quote', array_keys($this->opTypeNames))) . ')#';
		$this->unicodeWhitespace = $unicodeWS;
	}

	public function init($source, $filename = '', $lineno = 1) {
		$this->source = str_replace(array("\r\n", "\n\r", "\r"), "\n", $source);
		$this->source .= "\n";
		$this->filename = $filename ? $filename : '[inline]';
		$this->lineno = $lineno;
		$this->licenses = array();

		$this->cursor = 0;
		$this->tokens = array();
		$this->tokenIndex = 0;
		$this->lookahead = 0;
		$this->scanNewlines = false;
		$this->scanOperand = true;

		preg_match_all('~[\s\S]~u', $this->source, $m);
		$this->chars = $m[0];

		$this->length = count($this->chars);
	}

	public function getChar($offset = 0) {
		return $this->cursor + $offset < $this->length ? $this->chars[$this->cursor + $offset] : false;
	}

	protected function getCodePoint($offset = 0) {
		if (false === ($c = $this->getChar($offset))) {
			return false;
		}

		$points = $this->toCodePoints($c);
		return $points[0];
	}

	protected function isWhitespace($offset = 0) {
		if (ctype_space($c = $this->getChar($offset))) {
			return $c;
		}

		if (!$points = $this->toCodePoints($c)) {
			return false;
		}

		$point = $points[0];

		return $point == 0x0085 || $point == 0x00A0
			|| $point == 0x1680 || $point == 0x180E
			|| ($point >= 0x2000 && $point <= 0x200A)
			|| $point == 0x2028 || $point == 0x2029
			|| $point == 0x202F || $point == 0x205F
			|| $point == 0x3000 ? $c : false;
	}
	protected function isOctalDigit($peek = 0) {
		$c = $this->getChar($peek);
		return $c === '0' || $c === '1' || $c === '2' || $c === '3'
			|| $c === '4' || $c === '5' || $c === '6' || $c === '7' ? $c : false;
	}
	protected function matchIdentifier() {
		$match = '';
		$i = 0;
		while (false !== ($c = $this->getChar($i))) {
			if ($c === '_' || $c === '$' || ctype_alpha($c)) {
				$match .= $c;
			} elseif ($i && ctype_digit($c)) {
				$match .= $c;
			} elseif ($c === '\\') {
				if ($this->getChar($i + 1) === 'u') {
					$match .= '\\u';
					$i += 2;
					for ($j = 0; $j < 4; ++$j) {
						if (!ctype_xdigit($c = $this->getChar($i))) {
							throw $this->newSyntaxError('expected hex digit', $this->cursor + $i);
						}

						$match .= $c;
						++$i;
					}

					continue;
				} else {
					break;
				}
			} elseif ($c[0] >= "\x80" && $this->isIdentifierPart($c, $i === 0)) {
				$match .= $c;
			} else {
				break;
			}
			++$i;
		}

		if ($match) {
			return $match;
		}

		return false;
	}

	protected function isIdentifierPart($c, $start = false) {
		//echo $c . "\n";

		if (!$points = $this->toCodePoints($c)) {
			return false;
		}

		$cp = $points[0];

		if ($cp === 0xaa || $cp === 0xb5 || $cp === 0xba || ($cp >= 0xc0 && $cp <= 0xd6) || ($cp >= 0xd8 && $cp <= 0xf6)
				|| ($cp >= 0xf8 && $cp <= 0x02c1) || ($cp >= 0x02c6 && $cp <= 0x02d1) || ($cp >= 0x02e0 && $cp <= 0x02e4) || $cp === 0x02ec
				|| $cp === 0x02ee || ($cp >= 0x0370 && $cp <= 0x0374) || $cp === 0x0376 || $cp === 0x0377 || ($cp >= 0x037a && $cp <= 0x037d)
				|| $cp === 0x0386 || ($cp >= 0x0388 && $cp <= 0x038a) || $cp === 0x038c || ($cp >= 0x038e && $cp <= 0x03a1)
				|| ($cp >= 0x03a3 && $cp <= 0x03f5) || ($cp >= 0x03f7 && $cp <= 0x0481) || ($cp >= 0x048a && $cp <= 0x0527)
				|| ($cp >= 0x0531 && $cp <= 0x0556) || $cp === 0x0559 || ($cp >= 0x0561 && $cp <= 0x0587) || ($cp >= 0x05d0 && $cp <= 0x05ea)
				|| ($cp >= 0x05f0 && $cp <= 0x05f2) || ($cp >= 0x0620 && $cp <= 0x064a) || $cp === 0x066e || $cp === 0x066f
				|| ($cp >= 0x0671 && $cp <= 0x06d3) || $cp === 0x06d5 || $cp === 0x06e5 || $cp === 0x06e6 || $cp === 0x06ee
				|| $cp === 0x06ef || ($cp >= 0x06fa && $cp <= 0x06fc) || $cp === 0x06ff || $cp === 0x0710 || ($cp >= 0x0712 && $cp <= 0x072f)
				|| ($cp >= 0x074d && $cp <= 0x07a5) || $cp === 0x07b1 || ($cp >= 0x07ca && $cp <= 0x07ea) || $cp === 0x07f4
				|| $cp === 0x07f5 || $cp === 0x07fa || ($cp >= 0x0800 && $cp <= 0x0815) || $cp === 0x081a || $cp === 0x0824 || $cp === 0x0828
				|| ($cp >= 0x0840 && $cp <= 0x0858) || $cp === 0x08a0 || ($cp >= 0x08a2 && $cp <= 0x08ac) || ($cp >= 0x0904 && $cp <= 0x0939)
				|| $cp === 0x093d || $cp === 0x0950 || ($cp >= 0x0958 && $cp <= 0x0961) || ($cp >= 0x0971 && $cp <= 0x0977)
				|| ($cp >= 0x0979 && $cp <= 0x097f) || ($cp >= 0x0985 && $cp <= 0x098c) || $cp === 0x098f || $cp === 0x0990
				|| ($cp >= 0x0993 && $cp <= 0x09a8) || ($cp >= 0x09aa && $cp <= 0x09b0) || $cp === 0x09b2 || ($cp >= 0x09b6 && $cp <= 0x09b9)
				|| $cp === 0x09bd || $cp === 0x09ce || $cp === 0x09dc || $cp === 0x09dd || ($cp >= 0x09df && $cp <= 0x09e1) || $cp === 0x09f0
				|| $cp === 0x09f1 || ($cp >= 0x0a05 && $cp <= 0x0a0a) || $cp === 0x0a0f || $cp === 0x0a10 || ($cp >= 0x0a13 && $cp <= 0x0a28)
				|| ($cp >= 0x0a2a && $cp <= 0x0a30) || $cp === 0x0a32 || $cp === 0x0a33 || $cp === 0x0a35 || $cp === 0x0a36 || $cp === 0x0a38
				|| $cp === 0x0a39 || ($cp >= 0x0a59 && $cp <= 0x0a5c) || $cp === 0x0a5e || ($cp >= 0x0a72 && $cp <= 0x0a74) || ($cp >= 0x0a85 && $cp <= 0x0a8d)
				|| ($cp >= 0x0a8f && $cp <= 0x0a91) || ($cp >= 0x0a93 && $cp <= 0x0aa8) || ($cp >= 0x0aaa && $cp <= 0x0ab0) || $cp === 0x0ab2
				|| $cp === 0x0ab3 || ($cp >= 0x0ab5 && $cp <= 0x0ab9) || $cp === 0x0abd || $cp === 0x0ad0 || $cp === 0x0ae0 || $cp === 0x0ae1
				|| ($cp >= 0x0b05 && $cp <= 0x0b0c) || $cp === 0x0b0f || $cp === 0x0b10 || ($cp >= 0x0b13 && $cp <= 0x0b28) || ($cp >= 0x0b2a && $cp <= 0x0b30)
				|| $cp === 0x0b32 || $cp === 0x0b33 || ($cp >= 0x0b35 && $cp <= 0x0b39) || $cp === 0x0b3d || $cp === 0x0b5c || $cp === 0x0b5d
				|| ($cp >= 0x0b5f && $cp <= 0x0b61) || $cp === 0x0b71 || $cp === 0x0b83 || ($cp >= 0x0b85 && $cp <= 0x0b8a) || ($cp >= 0x0b8e && $cp <= 0x0b90)
				|| ($cp >= 0x0b92 && $cp <= 0x0b95) || $cp === 0x0b99 || $cp === 0x0b9a || $cp === 0x0b9c || $cp === 0x0b9e || $cp === 0x0b9f
				|| $cp === 0x0ba3 || $cp === 0x0ba4 || ($cp >= 0x0ba8 && $cp <= 0x0baa) || ($cp >= 0x0bae && $cp <= 0x0bb9) || $cp === 0x0bd0
				|| ($cp >= 0x0c05 && $cp <= 0x0c0c) || ($cp >= 0x0c0e && $cp <= 0x0c10) || ($cp >= 0x0c12 && $cp <= 0x0c28) || ($cp >= 0x0c2a && $cp <= 0x0c33)
				|| ($cp >= 0x0c35 && $cp <= 0x0c39) || $cp === 0x0c3d || $cp === 0x0c58 || $cp === 0x0c59 || $cp === 0x0c60 || $cp === 0x0c61
				|| ($cp >= 0x0c85 && $cp <= 0x0c8c) || ($cp >= 0x0c8e && $cp <= 0x0c90) || ($cp >= 0x0c92 && $cp <= 0x0ca8) || ($cp >= 0x0caa && $cp <= 0x0cb3)
				|| ($cp >= 0x0cb5 && $cp <= 0x0cb9) || $cp === 0x0cbd || $cp === 0x0cde || $cp === 0x0ce0 || $cp === 0x0ce1 || $cp === 0x0cf1
				|| $cp === 0x0cf2 || ($cp >= 0x0d05 && $cp <= 0x0d0c) || ($cp >= 0x0d0e && $cp <= 0x0d10) || ($cp >= 0x0d12 && $cp <= 0x0d3a)
				|| $cp === 0x0d3d || $cp === 0x0d4e || $cp === 0x0d60 || $cp === 0x0d61 || ($cp >= 0x0d7a && $cp <= 0x0d7f) || ($cp >= 0x0d85 && $cp <= 0x0d96)
				|| ($cp >= 0x0d9a && $cp <= 0x0db1) || ($cp >= 0x0db3 && $cp <= 0x0dbb) || $cp === 0x0dbd || ($cp >= 0x0dc0 && $cp <= 0x0dc6)
				|| ($cp >= 0x0e01 && $cp <= 0x0e30) || $cp === 0x0e32 || $cp === 0x0e33 || ($cp >= 0x0e40 && $cp <= 0x0e46) || $cp === 0x0e81
				|| $cp === 0x0e82 || $cp === 0x0e84 || $cp === 0x0e87 || $cp === 0x0e88 || $cp === 0x0e8a || $cp === 0x0e8d || ($cp >= 0x0e94 && $cp <= 0x0e97)
				|| ($cp >= 0x0e99 && $cp <= 0x0e9f) || ($cp >= 0x0ea1 && $cp <= 0x0ea3) || $cp === 0x0ea5 || $cp === 0x0ea7 || $cp === 0x0eaa
				|| $cp === 0x0eab || ($cp >= 0x0ead && $cp <= 0x0eb0) || $cp === 0x0eb2 || $cp === 0x0eb3 || $cp === 0x0ebd || ($cp >= 0x0ec0 && $cp <= 0x0ec4)
				|| $cp === 0x0ec6 || ($cp >= 0x0edc && $cp <= 0x0edf) || $cp === 0x0f00 || ($cp >= 0x0f40 && $cp <= 0x0f47) || ($cp >= 0x0f49 && $cp <= 0x0f6c)
				|| ($cp >= 0x0f88 && $cp <= 0x0f8c) || ($cp >= 0x1000 && $cp <= 0x102a) || $cp === 0x103f || ($cp >= 0x1050 && $cp <= 0x1055)
				|| ($cp >= 0x105a && $cp <= 0x105d) || $cp === 0x1061 || $cp === 0x1065 || $cp === 0x1066 || ($cp >= 0x106e && $cp <= 0x1070)
				|| ($cp >= 0x1075 && $cp <= 0x1081) || $cp === 0x108e || ($cp >= 0x10a0 && $cp <= 0x10c5) || $cp === 0x10c7 || $cp === 0x10cd
				|| ($cp >= 0x10d0 && $cp <= 0x10fa) || ($cp >= 0x10fc && $cp <= 0x1248) || ($cp >= 0x124a && $cp <= 0x124d) || ($cp >= 0x1250 && $cp <= 0x1256)
				|| $cp === 0x1258 || ($cp >= 0x125a && $cp <= 0x125d) || ($cp >= 0x1260 && $cp <= 0x1288) || ($cp >= 0x128a && $cp <= 0x128d)
				|| ($cp >= 0x1290 && $cp <= 0x12b0) || ($cp >= 0x12b2 && $cp <= 0x12b5) || ($cp >= 0x12b8 && $cp <= 0x12be) || $cp === 0x12c0
				|| ($cp >= 0x12c2 && $cp <= 0x12c5) || ($cp >= 0x12c8 && $cp <= 0x12d6) || ($cp >= 0x12d8 && $cp <= 0x1310) || ($cp >= 0x1312 && $cp <= 0x1315)
				|| ($cp >= 0x1318 && $cp <= 0x135a) || ($cp >= 0x1380 && $cp <= 0x138f) || ($cp >= 0x13a0 && $cp <= 0x13f4) || ($cp >= 0x1401 && $cp <= 0x166c)
				|| ($cp >= 0x166f && $cp <= 0x167f) || ($cp >= 0x1681 && $cp <= 0x169a) || ($cp >= 0x16a0 && $cp <= 0x16ea) || ($cp >= 0x16ee && $cp <= 0x16f0)
				|| ($cp >= 0x1700 && $cp <= 0x170c) || ($cp >= 0x170e && $cp <= 0x1711) || ($cp >= 0x1720 && $cp <= 0x1731) || ($cp >= 0x1740 && $cp <= 0x1751)
				|| ($cp >= 0x1760 && $cp <= 0x176c) || ($cp >= 0x176e && $cp <= 0x1770) || ($cp >= 0x1780 && $cp <= 0x17b3) || $cp === 0x17d7
				|| $cp === 0x17dc || ($cp >= 0x1820 && $cp <= 0x1877) || ($cp >= 0x1880 && $cp <= 0x18a8) || $cp === 0x18aa || ($cp >= 0x18b0 && $cp <= 0x18f5)
				|| ($cp >= 0x1900 && $cp <= 0x191c) || ($cp >= 0x1950 && $cp <= 0x196d) || ($cp >= 0x1970 && $cp <= 0x1974) || ($cp >= 0x1980 && $cp <= 0x19ab)
				|| ($cp >= 0x19c1 && $cp <= 0x19c7) || ($cp >= 0x1a00 && $cp <= 0x1a16) || ($cp >= 0x1a20 && $cp <= 0x1a54) || $cp === 0x1aa7
				|| ($cp >= 0x1b05 && $cp <= 0x1b33) || ($cp >= 0x1b45 && $cp <= 0x1b4b) || ($cp >= 0x1b83 && $cp <= 0x1ba0) || $cp === 0x1bae
				|| $cp === 0x1baf || ($cp >= 0x1bba && $cp <= 0x1be5) || ($cp >= 0x1c00 && $cp <= 0x1c23) || ($cp >= 0x1c4d && $cp <= 0x1c4f)
				|| ($cp >= 0x1c5a && $cp <= 0x1c7d) || ($cp >= 0x1ce9 && $cp <= 0x1cec) || ($cp >= 0x1cee && $cp <= 0x1cf1) || $cp === 0x1cf5
				|| $cp === 0x1cf6 || ($cp >= 0x1d00 && $cp <= 0x1dbf) || ($cp >= 0x1e00 && $cp <= 0x1f15) || ($cp >= 0x1f18 && $cp <= 0x1f1d)
				|| ($cp >= 0x1f20 && $cp <= 0x1f45) || ($cp >= 0x1f48 && $cp <= 0x1f4d) || ($cp >= 0x1f50 && $cp <= 0x1f57) || $cp === 0x1f59
				|| $cp === 0x1f5b || $cp === 0x1f5d || ($cp >= 0x1f5f && $cp <= 0x1f7d) || ($cp >= 0x1f80 && $cp <= 0x1fb4) || ($cp >= 0x1fb6 && $cp <= 0x1fbc)
				|| $cp === 0x1fbe || ($cp >= 0x1fc2 && $cp <= 0x1fc4) || ($cp >= 0x1fc6 && $cp <= 0x1fcc) || ($cp >= 0x1fd0 && $cp <= 0x1fd3)
				|| ($cp >= 0x1fd6 && $cp <= 0x1fdb) || ($cp >= 0x1fe0 && $cp <= 0x1fec) || ($cp >= 0x1ff2 && $cp <= 0x1ff4) || ($cp >= 0x1ff6 && $cp <= 0x1ffc)
				|| $cp === 0x2071 || $cp === 0x207f || ($cp >= 0x2090 && $cp <= 0x209c) || $cp === 0x2102 || $cp === 0x2107 || ($cp >= 0x210a && $cp <= 0x2113)
				|| $cp === 0x2115 || ($cp >= 0x2119 && $cp <= 0x211d) || $cp === 0x2124 || $cp === 0x2126 || $cp === 0x2128 || ($cp >= 0x212a && $cp <= 0x212d)
				|| ($cp >= 0x212f && $cp <= 0x2139) || ($cp >= 0x213c && $cp <= 0x213f) || ($cp >= 0x2145 && $cp <= 0x2149) || $cp === 0x214e
				|| ($cp >= 0x2160 && $cp <= 0x2188) || ($cp >= 0x2c00 && $cp <= 0x2c2e) || ($cp >= 0x2c30 && $cp <= 0x2c5e) || ($cp >= 0x2c60 && $cp <= 0x2ce4)
				|| ($cp >= 0x2ceb && $cp <= 0x2cee) || $cp === 0x2cf2 || $cp === 0x2cf3 || ($cp >= 0x2d00 && $cp <= 0x2d25) || $cp === 0x2d27
				|| $cp === 0x2d2d || ($cp >= 0x2d30 && $cp <= 0x2d67) || $cp === 0x2d6f || ($cp >= 0x2d80 && $cp <= 0x2d96) || ($cp >= 0x2da0 && $cp <= 0x2da6)
				|| ($cp >= 0x2da8 && $cp <= 0x2dae) || ($cp >= 0x2db0 && $cp <= 0x2db6) || ($cp >= 0x2db8 && $cp <= 0x2dbe) || ($cp >= 0x2dc0 && $cp <= 0x2dc6)
				|| ($cp >= 0x2dc8 && $cp <= 0x2dce) || ($cp >= 0x2dd0 && $cp <= 0x2dd6) || ($cp >= 0x2dd8 && $cp <= 0x2dde) || $cp === 0x2e2f
				|| ($cp >= 0x3005 && $cp <= 0x3007) || ($cp >= 0x3021 && $cp <= 0x3029) || ($cp >= 0x3031 && $cp <= 0x3035) || ($cp >= 0x3038 && $cp <= 0x303c)
				|| ($cp >= 0x3041 && $cp <= 0x3096) || ($cp >= 0x309d && $cp <= 0x309f) || ($cp >= 0x30a1 && $cp <= 0x30fa) || ($cp >= 0x30fc && $cp <= 0x30ff)
				|| ($cp >= 0x3105 && $cp <= 0x312d) || ($cp >= 0x3131 && $cp <= 0x318e) || ($cp >= 0x31a0 && $cp <= 0x31ba) || ($cp >= 0x31f0 && $cp <= 0x31ff)
				|| ($cp >= 0x3400 && $cp <= 0x4db5) || ($cp >= 0x4e00 && $cp <= 0x9fcc) || ($cp >= 0xa000 && $cp <= 0xa48c) || ($cp >= 0xa4d0 && $cp <= 0xa4fd)
				|| ($cp >= 0xa500 && $cp <= 0xa60c) || ($cp >= 0xa610 && $cp <= 0xa61f) || $cp === 0xa62a || $cp === 0xa62b || ($cp >= 0xa640 && $cp <= 0xa66e)
				|| ($cp >= 0xa67f && $cp <= 0xa697) || ($cp >= 0xa6a0 && $cp <= 0xa6ef) || ($cp >= 0xa717 && $cp <= 0xa71f) || ($cp >= 0xa722 && $cp <= 0xa788)
				|| ($cp >= 0xa78b && $cp <= 0xa78e) || ($cp >= 0xa790 && $cp <= 0xa793) || ($cp >= 0xa7a0 && $cp <= 0xa7aa) || ($cp >= 0xa7f8 && $cp <= 0xa801)
				|| ($cp >= 0xa803 && $cp <= 0xa805) || ($cp >= 0xa807 && $cp <= 0xa80a) || ($cp >= 0xa80c && $cp <= 0xa822) || ($cp >= 0xa840 && $cp <= 0xa873)
				|| ($cp >= 0xa882 && $cp <= 0xa8b3) || ($cp >= 0xa8f2 && $cp <= 0xa8f7) || $cp === 0xa8fb || ($cp >= 0xa90a && $cp <= 0xa925)
				|| ($cp >= 0xa930 && $cp <= 0xa946) || ($cp >= 0xa960 && $cp <= 0xa97c) || ($cp >= 0xa984 && $cp <= 0xa9b2) || $cp === 0xa9cf
				|| ($cp >= 0xaa00 && $cp <= 0xaa28) || ($cp >= 0xaa40 && $cp <= 0xaa42) || ($cp >= 0xaa44 && $cp <= 0xaa4b) || ($cp >= 0xaa60 && $cp <= 0xaa76)
				|| $cp === 0xaa7a || ($cp >= 0xaa80 && $cp <= 0xaaaf) || $cp === 0xaab1 || $cp === 0xaab5 || $cp === 0xaab6 || ($cp >= 0xaab9 && $cp <= 0xaabd)
				|| $cp === 0xaac0 || $cp === 0xaac2 || ($cp >= 0xaadb && $cp <= 0xaadd) || ($cp >= 0xaae0 && $cp <= 0xaaea) || ($cp >= 0xaaf2 && $cp <= 0xaaf4)
				|| ($cp >= 0xab01 && $cp <= 0xab06) || ($cp >= 0xab09 && $cp <= 0xab0e) || ($cp >= 0xab11 && $cp <= 0xab16) || ($cp >= 0xab20 && $cp <= 0xab26)
				|| ($cp >= 0xab28 && $cp <= 0xab2e) || ($cp >= 0xabc0 && $cp <= 0xabe2) || ($cp >= 0xac00 && $cp <= 0xd7a3) || ($cp >= 0xd7b0 && $cp <= 0xd7c6)
				|| ($cp >= 0xd7cb && $cp <= 0xd7fb) || ($cp >= 0xf900 && $cp <= 0xfa6d) || ($cp >= 0xfa70 && $cp <= 0xfad9) || ($cp >= 0xfb00 && $cp <= 0xfb06)
				|| ($cp >= 0xfb13 && $cp <= 0xfb17) || $cp === 0xfb1d || ($cp >= 0xfb1f && $cp <= 0xfb28) || ($cp >= 0xfb2a && $cp <= 0xfb36)
				|| ($cp >= 0xfb38 && $cp <= 0xfb3c) || $cp === 0xfb3e || $cp === 0xfb40 || $cp === 0xfb41 || $cp === 0xfb43 || $cp === 0xfb44
				|| ($cp >= 0xfb46 && $cp <= 0xfbb1) || ($cp >= 0xfbd3 && $cp <= 0xfd3d) || ($cp >= 0xfd50 && $cp <= 0xfd8f) || ($cp >= 0xfd92 && $cp <= 0xfdc7)
				|| ($cp >= 0xfdf0 && $cp <= 0xfdfb) || ($cp >= 0xfe70 && $cp <= 0xfe74) || ($cp >= 0xfe76 && $cp <= 0xfefc) || ($cp >= 0xff21 && $cp <= 0xff3a)
				|| ($cp >= 0xff41 && $cp <= 0xff5a) || ($cp >= 0xff66 && $cp <= 0xffbe) || ($cp >= 0xffc2 && $cp <= 0xffc7) || ($cp >= 0xffca && $cp <= 0xffcf)
				|| ($cp >= 0xffd2 && $cp <= 0xffd7) || ($cp >= 0xffda && $cp <= 0xffdc)) {
			return true;
		}

		if (!$start && (($cp >= 0x0300 && $cp <= 0x0374) || ($cp >= 0x0483 && $cp <= 0x0487) || ($cp >= 0x0591 && $cp <= 0x05bd) || $cp === 0x05bf
			|| $cp === 0x05c1 || $cp === 0x05c2 || $cp === 0x05c4 || $cp === 0x05c5 || $cp === 0x05c7 || ($cp >= 0x0610 && $cp <= 0x061a)
			|| ($cp >= 0x0620 && $cp <= 0x0669) || ($cp >= 0x06d3 && $cp <= 0x06dc) || ($cp >= 0x06df && $cp <= 0x06e8) || ($cp >= 0x06ea && $cp <= 0x06fc)
			|| $cp === 0x074a || ($cp >= 0x074d && $cp <= 0x07c0) || ($cp >= 0x0800 && $cp <= 0x082d) || ($cp >= 0x0840 && $cp <= 0x085b)
			|| ($cp >= 0x08e4 && $cp <= 0x08fe) || ($cp >= 0x0900 && $cp <= 0x0963) || ($cp >= 0x0966 && $cp <= 0x096f) || ($cp >= 0x0981 && $cp <= 0x0983)
			|| ($cp >= 0x09bc && $cp <= 0x09c4) || $cp === 0x09c7 || $cp === 0x09c8 || ($cp >= 0x09cb && $cp <= 0x09d7) || ($cp >= 0x09df && $cp <= 0x09e3)
			|| ($cp >= 0x09e6 && $cp <= 0x0a01) || $cp === 0x0a03 || $cp === 0x0a3c || ($cp >= 0x0a3e && $cp <= 0x0a42) || $cp === 0x0a47
			|| $cp === 0x0a48 || ($cp >= 0x0a4b && $cp <= 0x0a4d) || $cp === 0x0a51 || ($cp >= 0x0a66 && $cp <= 0x0a75) || ($cp >= 0x0a81 && $cp <= 0x0a83)
			|| ($cp >= 0x0abc && $cp <= 0x0ac5) || ($cp >= 0x0ac7 && $cp <= 0x0ac9) || ($cp >= 0x0acb && $cp <= 0x0acd) || $cp === 0x0ae3
			|| ($cp >= 0x0ae6 && $cp <= 0x0aef) || ($cp >= 0x0b01 && $cp <= 0x0b03) || ($cp >= 0x0b3c && $cp <= 0x0b44) || $cp === 0x0b47
			|| $cp === 0x0b48 || ($cp >= 0x0b4b && $cp <= 0x0b4d) || $cp === 0x0b56 || $cp === 0x0b57 || ($cp >= 0x0b5f && $cp <= 0x0b63)
			|| ($cp >= 0x0b66 && $cp <= 0x0b6f) || $cp === 0x0b82 || ($cp >= 0x0bbe && $cp <= 0x0bc2) || ($cp >= 0x0bc6 && $cp <= 0x0bc8)
			|| ($cp >= 0x0bca && $cp <= 0x0bcd) || $cp === 0x0bd7 || ($cp >= 0x0be6 && $cp <= 0x0bef) || ($cp >= 0x0c01 && $cp <= 0x0c03)
			|| $cp === 0x0c44 || ($cp >= 0x0c46 && $cp <= 0x0c48) || ($cp >= 0x0c4a && $cp <= 0x0c4d) || $cp === 0x0c55 || ($cp >= 0x0c56 && $cp <= 0x0c63)
			|| ($cp >= 0x0c66 && $cp <= 0x0c6f) || $cp === 0x0c82 || $cp === 0x0c83 || ($cp >= 0x0cbc && $cp <= 0x0cc4) || ($cp >= 0x0cc6 && $cp <= 0x0cc8)
			|| ($cp >= 0x0cca && $cp <= 0x0ccd) || $cp === 0x0cd5 || ($cp >= 0x0cd6 && $cp <= 0x0ce3) || ($cp >= 0x0ce6 && $cp <= 0x0cef)
			|| $cp === 0x0d02 || ($cp >= 0x0d03 && $cp <= 0x0d44) || ($cp >= 0x0d46 && $cp <= 0x0d48) || ($cp >= 0x0d4a && $cp <= 0x0d57)
			|| $cp === 0x0d63 || ($cp >= 0x0d66 && $cp <= 0x0d6f) || $cp === 0x0d82 || $cp === 0x0d83 || $cp === 0x0dca || ($cp >= 0x0dcf && $cp <= 0x0dd4)
			|| $cp === 0x0dd6 || ($cp >= 0x0dd8 && $cp <= 0x0ddf) || $cp === 0x0df2 || $cp === 0x0df3 || ($cp >= 0x0e01 && $cp <= 0x0e3a)
			|| ($cp >= 0x0e40 && $cp <= 0x0e4e) || ($cp >= 0x0e50 && $cp <= 0x0e59) || ($cp >= 0x0ead && $cp <= 0x0eb9) || ($cp >= 0x0ebb && $cp <= 0x0ec8)
			|| $cp === 0x0ecd || ($cp >= 0x0ed0 && $cp <= 0x0ed9) || $cp === 0x0f18 || $cp === 0x0f19 || ($cp >= 0x0f20 && $cp <= 0x0f29)
			|| $cp === 0x0f35 || $cp === 0x0f37 || $cp === 0x0f39 || ($cp >= 0x0f3e && $cp <= 0x0f47) || ($cp >= 0x0f71 && $cp <= 0x0f84)
			|| ($cp >= 0x0f86 && $cp <= 0x0f97) || ($cp >= 0x0f99 && $cp <= 0x0fbc) || $cp === 0x0fc6 || ($cp >= 0x1000 && $cp <= 0x1049)
			|| ($cp >= 0x1050 && $cp <= 0x109d) || ($cp >= 0x135d && $cp <= 0x135f) || ($cp >= 0x170e && $cp <= 0x1714) || ($cp >= 0x1720 && $cp <= 0x1734)
			|| ($cp >= 0x1740 && $cp <= 0x1753) || $cp === 0x1772 || $cp === 0x1773 || ($cp >= 0x1780 && $cp <= 0x17d3) || $cp === 0x17dd
			|| ($cp >= 0x17e0 && $cp <= 0x17e9) || ($cp >= 0x180b && $cp <= 0x180d) || ($cp >= 0x1810 && $cp <= 0x1819) || ($cp >= 0x1880 && $cp <= 0x1920)
			|| $cp === 0x192b || ($cp >= 0x1930 && $cp <= 0x193b) || ($cp >= 0x1946 && $cp <= 0x196d) || ($cp >= 0x19b0 && $cp <= 0x19c9)
			|| ($cp >= 0x19d0 && $cp <= 0x19d9) || ($cp >= 0x1a00 && $cp <= 0x1a1b) || ($cp >= 0x1a20 && $cp <= 0x1a5e) || ($cp >= 0x1a60 && $cp <= 0x1a7c)
			|| ($cp >= 0x1a7f && $cp <= 0x1a89) || ($cp >= 0x1a90 && $cp <= 0x1a99) || ($cp >= 0x1b00 && $cp <= 0x1b4b) || ($cp >= 0x1b50 && $cp <= 0x1b59)
			|| ($cp >= 0x1b6b && $cp <= 0x1b73) || ($cp >= 0x1b80 && $cp <= 0x1bf3) || ($cp >= 0x1c00 && $cp <= 0x1c37) || ($cp >= 0x1c40 && $cp <= 0x1c49)
			|| ($cp >= 0x1c4d && $cp <= 0x1c7d) || ($cp >= 0x1cd0 && $cp <= 0x1cd2) || ($cp >= 0x1cd4 && $cp <= 0x1d00) || $cp === 0x1de6
			|| ($cp >= 0x1dfc && $cp <= 0x1f15) || $cp === 0x200c || $cp === 0x200d || $cp === 0x203f || $cp === 0x2040 || $cp === 0x2054
			|| ($cp >= 0x20d0 && $cp <= 0x20dc) || $cp === 0x20e1 || ($cp >= 0x20e5 && $cp <= 0x20f0) || ($cp >= 0x2ceb && $cp <= 0x2d7f)
			|| $cp === 0x2d96 || ($cp >= 0x2de0 && $cp <= 0x2dff) || ($cp >= 0x3021 && $cp <= 0x302f) || $cp === 0x3099 || $cp === 0x309a
			|| ($cp >= 0xa610 && $cp <= 0xa640) || $cp === 0xa66f || ($cp >= 0xa674 && $cp <= 0xa67d) || ($cp >= 0xa69f && $cp <= 0xa6f1)
			|| ($cp >= 0xa7f8 && $cp <= 0xa827) || ($cp >= 0xa880 && $cp <= 0xa8c4) || ($cp >= 0xa8d0 && $cp <= 0xa8d9) || ($cp >= 0xa8e0 && $cp <= 0xa8f7)
			|| ($cp >= 0xa900 && $cp <= 0xa92d) || ($cp >= 0xa930 && $cp <= 0xa953) || ($cp >= 0xa980 && $cp <= 0xa9c0) || $cp === 0xa9d9
			|| ($cp >= 0xaa00 && $cp <= 0xaa36) || ($cp >= 0xaa40 && $cp <= 0xaa4d) || ($cp >= 0xaa50 && $cp <= 0xaa59) || $cp === 0xaa7b
			|| ($cp >= 0xaa80 && $cp <= 0xaae0) || $cp === 0xaaef || ($cp >= 0xaaf2 && $cp <= 0xaaf6) || ($cp >= 0xabc0 && $cp <= 0xabea)
			|| $cp === 0xabec || $cp === 0xabed || ($cp >= 0xabf0 && $cp <= 0xabf9) || $cp === 0xfb28 || ($cp >= 0xfe00 && $cp <= 0xfe0f)
			|| ($cp >= 0xfe20 && $cp <= 0xfe26) || $cp === 0xfe33 || $cp === 0xfe34 || ($cp >= 0xfe4d && $cp <= 0xfe4f) || ($cp >= 0xff10 && $cp <= 0xff19)
			|| $cp === 0xff3f)) {
				return true;
		}
	}

	public function isDone() {
		return $this->peek() === TOKEN_END;
	}

	public function match($tt) {
		return $this->get() === $tt || $this->unget();
	}

	public function mustMatch($tt) {
		if ($this->get() !== $tt) {
			throw $this->newSyntaxError('Unexpected token ' . $this->currentToken()->value . '; token ' . $tt . ' expected');
		}

		return $this->currentToken();
	}

	public function peek() {
		if ($this->lookahead) {
			$next = $this->tokens[($this->tokenIndex + $this->lookahead) & 3];
			if ($this->scanNewlines && $next->lineno !== $this->lineno) {
				$tt = TOKEN_NEWLINE;
			} else {
				$tt = $next->type;
			}
		} else {
			$tt = $this->get();
			$this->unget();
		}

		return $tt;
	}

	public function peekOnSameLine() {
		$this->scanNewlines = true;
		$tt = $this->peek();
		$this->scanNewlines = false;

		return $tt;
	}

	public function currentToken() {
		if (!empty($this->tokens)) {
			return $this->tokens[$this->tokenIndex];
		}
	}

	public function get($chunksize = 1000) {
		while($this->lookahead) {
			--$this->lookahead;
			$this->tokenIndex = ($this->tokenIndex + 1) & 3;
			$token = $this->tokens[$this->tokenIndex];
			if ($token->type !== TOKEN_NEWLINE || $this->scanNewlines) {
				return $token->type;
			}
		}

		$conditional_comment = false;
		$lastComment = null;
		// strip whitespace and comments
		for(;;) {
			while ($c = $this->isWhitespace()) {
				if ($c === "\n") {
					++$this->lineno;
					if ($this->scanNewlines) {
						$input = "\n";
						break 2;
					}
				}

				++$this->cursor;
			}

			if ($this->getChar() === '/') {
				switch ($this->getChar(1)) {
				case '/':
					$this->cursor += 2;
					while ($this->getChar() !== "\n") {
						++$this->cursor;
					}

					++$this->lineno;

					if ($this->scanNewlines) {
						$input = "\n";
						break 2;
					} else {
						++$this->cursor;
					}
					continue 2;
				case '*':
					$this->cursor += 2;
					$newlines = 0;
					$comment = '/*';

					while (false !== ($c = $this->getChar())) {
						$comment .= $c;

						if ($c === false) {
							throw $this->newSyntaxError('Unterminated comment', true);
						} elseif ($c === "\n") {
							++$this->cursor;
							++$this->lineno;
							++$newlines;
						} elseif ($c === '*') {
							++$this->cursor;
							if ($this->getChar() === '/') {
								$comment .= '/';

								if (substr($comment, 0, 3) === '/*!') {
									// simple unindent for properly formatted license comments
									$this->licenses[] = preg_replace('~^(?:\t+|(?: {4})+|(?:  )+)~m', '', $comment);
								}

								if ($this->scanNewlines && $newlines) {
									$input = "\n";
									break 3;
								} else {
									++$this->cursor;
									break 2;
								}
							}
						} else {
							++$this->cursor;
						}
					}
				break;
				default:
					break 2;
				}

				continue;
			}

			break;
		}

		if ($this->cursor >= $this->length) {
			$tt = TOKEN_END;
			$match = '';
		} elseif ($this->scanNewlines && isset($input) && $input === "\n") {
			$tt = TOKEN_NEWLINE;
			$match = "\n";
		} else {
			$i = 1;

			switch ($baseChar = $this->getChar()) {
				case '0':
					$c = $this->getChar(1);
					if ($c === 'x' || $c === 'X') {
						$match = '0' . $c;
						while (ctype_xdigit($c = $this->getChar(1 + $i))) {
							$match .= $c;
							++$i;
						}

						$tt = TOKEN_NUMBER;
						break;
					} elseif ($c !== '.' && false !== ($c = $this->isOctalDigit(1))) {
						$match = '0' . $c;
						while (false !== ($c = $this->isOctalDigit(1 + $i))) {
							$match .= $c;
							++$i;
						}

						$tt = TOKEN_NUMBER;
						break;
					}

					// FALL THROUGH
				case '1': case '2': case '3': case '4':
				case '5': case '6': case '7': case '8': case '9':
					$match = $baseChar;
					while (ctype_digit($c = $this->getChar($i))) {
						$match .= $c;
						++$i;
					}
					if ($c === '.') {
						$match .= '.';
						++$i;
					}
					while (ctype_digit($c = $this->getChar($i))) {
						$match .= $c;
						++$i;
					}
					if ($c === 'e' || $c === 'E') {
						$match .= 'e';
						$c = $this->getChar(++$i);
						if ($c === '+' || $c === '-') {
							$match .= $c;
							++$i;
						}
						while (ctype_digit($c = $this->getChar($i))) {
							$match .= $c;
							++$i;
						}
					}
					$tt = TOKEN_NUMBER;
					break;
				case '"':
				case "'":
					$match = $baseChar;
					while (false !== ($c = $this->getChar($i))) {
						$match .= $c;
						++$i;

						if ($c === $baseChar) {
							$tt = TOKEN_STRING;
							break 2;
						} elseif ($c === '\\') {
							$match .= $this->getChar($i);
							++$i;
						} elseif ($c === "\n") {
							break;
						}
					}

					throw $this->newSyntaxError('Unterminated string literal', true);
				case '/':
					if ($this->scanOperand) {
						$match = '/';
						$state = 0;
						while (false !== ($c = $this->getChar($i))) {
							$match .= $c;
							++$i;
							if ($c === '\\') {
								$match .= $this->getChar($i);
								++$i;
							} elseif ($state === 1) {
								if ($c === ']') {
									$state = 0;
								}
							} elseif ($c === "\n") {
								break;
							} else {
								if ($c === '/') {
									while (ctype_alpha($c = $this->getChar($i))) {
										$match .= $c;
										++$i;
									}
									$tt = TOKEN_REGEXP;
									break 2;
								} elseif ($c === '[') {
									$state = 1;
								}
							}
						}

						throw $this->newSyntaxError('Unterminated regex literal', true);
					}
				// FALL THROUGH
				case '|':
				case '^':
				case '&':
				case '<':
				case '>':
				case '+':
				case '-':
				case '*':
				case '%':
				case '=':
				case '!':
					$match = $baseChar . $this->getChar(1) . $this->getChar(2);
					while ($match) {
						if (isset($this->opTypeNames[$match])) {
							break;
						}
						$match = substr($match, 0, -1);
					}
					$op = $match;
					if (in_array($op, $this->assignOps) && $this->getChar(strlen($op)) === '=') {
						$tt = OP_ASSIGN;
						$match .= '=';
					} else {
						$tt = $op;
						if ($this->scanOperand) {
							if ($op === OP_PLUS) {
								$tt = OP_UNARY_PLUS;
							} elseif ($op === OP_MINUS) {
								$tt = OP_UNARY_MINUS;
							}
						}

						$op = null;
					}
					break;
				case '.':
					$match = '.';
					if (ctype_digit($c = $this->getChar(1))) {
						while (ctype_digit($c = $this->getChar($i))) {
							$match .= $c;
							++$i;
						}
						if ($c === 'e' || $c === 'E') {
							$match .= 'e';
							$c = $this->getChar(++$i);
							if ($c === '+' || $c === '-') {
								$match .= $c;
								++$i;
							}
							while (ctype_digit($c = $this->getChar($i))) {
								$match .= $c;
								++$i;
							}
						}
						$tt = TOKEN_NUMBER;
						break;
					}
				// FALL THROUGH
				case ';':
				case ',':
				case '?':
				case ':':
				case '~':
				case '[':
				case ']':
				case '{':
				case '}':
				case '(':
				case ')':
					// these are all single
					$match = $baseChar;
					$tt = $match;
					break;
				case '@':
					throw $this->newSyntaxError('Illegal @ token', true);
				case "\n":
					throw $this->newSyntaxError('Illegal newline token', true);
				default:
					if ($match = $this->matchIdentifier()) {
						$tt = in_array($match, $this->keywords) ? $match : TOKEN_IDENTIFIER;
					} else {
						throw $this->newSyntaxError('Illegal token (0x' . dechex($this->getCodePoint()) . ')', true);
					}
			}
		}
		if (is_array($match)) {
			$match = $match[0];
		}

		$this->tokenIndex = ($this->tokenIndex + 1) & 3;

		if (!isset($this->tokens[$this->tokenIndex])) {
			$this->tokens[$this->tokenIndex] = new JSToken();
		}

		$token = $this->tokens[$this->tokenIndex];
		$token->type = $tt;

		if ($tt === OP_ASSIGN) {
			$token->assignOp = $op;
		}

		$token->start = $this->cursor;

		if ($tt === TOKEN_IDENTIFIER) {
			$token->value = $this->decomposeUnicode($match);
		} else {
			$token->value = $match;
		}

		$this->cursor += mb_strlen($match, 'UTF-8');

		$token->end = $this->cursor;
		$token->lineno = $this->lineno;

		return $tt;
	}

	private function decomposeUnicode($original) {
		return preg_replace_callback('~\\\\u([a-fA-F0-9]{4})~', function ($m) {
			$cp = (int)base_convert($m[1], 16, 10);

			if($cp < 0x80) {
				$returnStr = chr($cp);
			} elseif($cp < 0x800) {
				$returnStr = chr(0xC0 | $cp >> 6)
					. chr(0x80 | ($cp & 0x3F));
			} elseif($cp < 0x10000) {
				$returnStr = chr(0xE0 | $cp >> 12)
					. chr(0x80 | ($cp >> 6 & 0x3F))
					. chr(0x80 | $cp & 0x3F);
			} else {
				$returnStr = chr(0xF0 | $cp >> 18)
					. chr(0x80 | ($cp >> 12 & 0x3F))
					. chr(0x80 | ($cp >> 6 & 0x3F))
					. chr(0x80 | $cp & 0x3F);
			}

			return $returnStr;
		}, $original);
	}

	protected function toCodePoints($string) {
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

	public function unget() {
		if (++$this->lookahead === 4) {
			throw $this->newSyntaxError('PANIC: too much lookahead!');
		}

		$this->tokenIndex = ($this->tokenIndex - 1) & 3;
	}

	public function newSyntaxError($m, $currentOffset = false) {
		return new Exception('Parse error: ' . $m
			. " in file '" . $this->filename . "' on line "
			. $this->lineno
			. "\n\n" . $this->errorContext((!$currentOffset ? $this->currentToken()->start : null) ?: $this->cursor) . "\n");
	}

	protected function errorContext($cursor) {
		$lastNewline = max(mb_strrpos($this->source, "\n", $cursor - $this->length) ?: -1, $cursor - 20) + 1;
		$nextNewline = min(mb_strpos($this->source, "\n", $cursor), $cursor + 20);

		$piece = mb_substr($this->source, $lastNewline, $nextNewline - $lastNewline);
		$fix = str_replace("\t", '    ', mb_substr($piece, 0, $cursor - $lastNewline));
		$piece = str_replace("\t", '    ', $piece);

		return $piece
			. "\n"
			. ($fix ? str_repeat('.', mb_strlen($fix)) : '') . '^';
	}
}

class JSToken {
	public $type;
	public $value;
	public $start;
	public $end;
	public $lineno;
	public $assignOp;
	public $comment;
}
