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
			} else {
				break;
			}
			++$i;
		}
		if ($match) {
			return $match;
		}
		return false;
		$input = $this->getInput(150);
		if (preg_match('~\A(?:\\\\u[0-9A-F]{4}|[$_\pL\p{Nl}]+)+(?:\\\\u[0-9A-F]{4}|[$_\pL\pN\p{Mn}\p{Mc}\p{Pc}\x{200c}\x{200d}]+)*~iu', $input, $match)) {
			return $match[0];
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

					while (false !== ($c = $this->getChar())) {
						if ($c === false) {
							throw $this->newSyntaxError('Unterminated comment', true);
						} elseif ($c === "\n") {
							++$this->cursor;
							++$this->lineno;
							++$newlines;
						} elseif ($c === '*') {
							++$this->cursor;
							if ($this->getChar() === '/') {
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

			if (false) {
			// don't want to support conditional comments just yet
			//if (!preg_match('~^/(?:\*(@(?:cc_on|if\s*\([^)]+\)|el(?:if\s*\([^)]+\)|se)|end))?[^*]*\*+(?:[^/][^*]*\*+)*/|/[^\n]*\n)~', $input, $match)) {
			if (!preg_match('~^/(?:\*[^*]*\*+(?:[^/][^*]*\*+)*/|/[^\n]*\n)~', $input, $match)) {
				if (!$chunksize) {
					break;
				}

				$chunksize = null;
				continue;
			}

			if (substr($match[0], 0, 3) === '/*!') {
				// simple unindent for properly formatted license comments
				$this->licenses[] = preg_replace('~^(?:\t+|(?: {4})+|(?:  )+)~m', '', $match[0]);
			}

			//if (!empty($match[1])) {
			//	$match[0] = '/*' . $match[1];
			//	$conditional_comment = true;
			//	break;
			//} else {
				$this->cursor += strlen($match[0]);
				$this->lineno += $c = substr_count($match[0], "\n");

				if ($c > 0 && $this->scanNewlines) {
					$input = "\n";
					--$this->cursor;
					break;
				}
			//}
			}
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
						throw $this->newSyntaxError('Illegal token'
							. (!$this->unicodeWhitespace && preg_match('~^[\t\v\f\s \p{Zs}]~u', $this->getChar()) ?
								': unicode-whitespace' : ' (0x' . dechex($this->getCodePoint()) . ')'
							), true
						);
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
		$lastNewline = max(mb_strrpos($this->source, "\n", $cursor - $this->length) + 1 ?: 0, $cursor - 20);
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
