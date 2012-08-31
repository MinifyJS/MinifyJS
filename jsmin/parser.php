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

define('TOKEN_END', 'TOKEN_END');
define('TOKEN_NUMBER', 'TOKEN_NUMBER');
define('TOKEN_IDENTIFIER', 'TOKEN_IDENTIFIER');
define('TOKEN_STRING', 'TOKEN_STRING');
define('TOKEN_REGEXP', 'TOKEN_REGEXP');
define('TOKEN_NEWLINE', 'TOKEN_NEWLINE');
define('TOKEN_UNDEFINED', 'TOKEN_UNDEFINED');
define('TOKEN_CONDCOMMENT_START', 'TOKEN_CONDCOMMENT_START');
define('TOKEN_CONDCOMMENT_END', 'TOKEN_CONDCOMMENT_END');

define('JS_SCRIPT', 'JS_SCRIPT');
define('JS_BLOCK', 'JS_BLOCK');
define('JS_LABEL', 'JS_LABEL');
define('JS_FOR_IN', 'JS_FOR_IN');
define('JS_CALL', 'JS_CALL');
define('JS_NEW_WITH_ARGS', 'JS_NEW_WITH_ARGS');
define('JS_INDEX', 'JS_INDEX');
define('JS_ARRAY_INIT', 'JS_ARRAY_INIT');
define('JS_OBJECT_INIT', 'JS_OBJECT_INIT');
define('JS_PROPERTY_INIT', 'JS_PROPERTY_INIT');
define('JS_GROUP', 'JS_GROUP');
define('JS_LIST', 'JS_LIST');

define('DECLARED_FORM', 'DECLARED_FORM');
define('EXPRESSED_FORM', 'EXPRESSED_FORM');
define('STATEMENT_FORM', 'STATEMENT_FORM');

require_once MIN_BASE . 'tokenizer.php';

class JSParser {
	private $t;
	private $licenses = array();

	private $opPrecedence = array(
		';' => 0,
		',' => 1,
		'=' => 2, '?' => 2, ':' => 2,
		'||' => 4,
		'&&' => 5,
		'|' => 6,
		'^' => 7,
		'&' => 8,
		'==' => 9, '!=' => 9, '===' => 9, '!==' => 9,
		'<' => 10, '<=' => 10, '>=' => 10, '>' => 10, 'in' => 10, 'instanceof' => 10,
		'<<' => 11, '>>' => 11, '>>>' => 11,
		'+' => 12, '-' => 12,
		'*' => 13, '/' => 13, '%' => 13,
		'delete' => 14, 'void' => 14, 'typeof' => 14,
		'!' => 14, '~' => 14, 'U+' => 14, 'U-' => 14,
		'++' => 15, '--' => 15,
		'new' => 16,
		'.' => 17,
		JS_NEW_WITH_ARGS => 0, JS_INDEX => 0, JS_CALL => 0,
		JS_ARRAY_INIT => 0, JS_OBJECT_INIT => 0, JS_GROUP => 0
	);

	private $opArity = array(
		',' => -2,
		'=' => 2,
		'?' => 3,
		'||' => 2,
		'&&' => 2,
		'|' => 2,
		'^' => 2,
		'&' => 2,
		'==' => 2, '!=' => 2, '===' => 2, '!==' => 2,
		'<' => 2, '<=' => 2, '>=' => 2, '>' => 2, 'in' => 2, 'instanceof' => 2,
		'<<' => 2, '>>' => 2, '>>>' => 2,
		'+' => 2, '-' => 2,
		'*' => 2, '/' => 2, '%' => 2,
		'delete' => 1, 'void' => 1, 'typeof' => 1,
		'!' => 1, '~' => 1, 'U+' => 1, 'U-' => 1,
		'++' => 1, '--' => 1,
		'new' => 1,
		'.' => 2,

		JS_NEW_WITH_ARGS => 2,
		JS_INDEX => 2,
		JS_CALL => 2,
		JS_ARRAY_INIT => 1,
		JS_OBJECT_INIT => 1,
		JS_GROUP => 1,
		TOKEN_CONDCOMMENT_START => 1,
		TOKEN_CONDCOMMENT_END => 1
	);

	public function __construct() { }

	public function parse($s, $f, $l, $unicodeWS) {
		$this->t = new JSTokenizer($unicodeWS);

		$s = str_replace(array("\r\n", "\n\r", "\r"), "\n", $s);
		$this->t->init($s, $f, $l);

		$x = new JSCompilerContext(false);
		$n = $this->Script($x);
		if (!$this->t->isDone()) {
			throw $this->t->newSyntaxError('Syntax error');
		}

		$this->licenses = $this->t->licenses;

		$this->t = null;

		return $n;
	}

	public function getLicenses() {
		return $this->licenses;
	}

	private function Script($x) {
		$n = $this->Statements($x);
		$n->context = $x;
		$n->type = JS_SCRIPT;

		return $n;
	}

	private function Statements($x) {
		$n = new JSNode($this->t, JS_BLOCK);
		$x->stmtStack[] = $n;

		while (!$this->t->isDone() && $this->t->peek() !== OP_RIGHT_CURLY) {
			$n->addNode($this->Statement($x));
		}

		array_pop($x->stmtStack);

		return $n;
	}

	private function Block($x) {
		$this->t->mustMatch(OP_LEFT_CURLY);
		$n = $this->Statements($x);
		$this->t->mustMatch(OP_RIGHT_CURLY);

		return $n;
	}

	private function Statement($x) {
		$tt = $this->t->get();
		$n2 = null;

		switch ($tt) {
		case KEYWORD_FUNCTION:
			return $this->FunctionDefinition($x, true, count($x->stmtStack) > 1 ? STATEMENT_FORM : DECLARED_FORM);
		case OP_LEFT_CURLY:
			$n = $this->Statements($x);
			$this->t->mustMatch(OP_RIGHT_CURLY);

			return $n;
		case KEYWORD_IF:
			$n = new JSNode($this->t);
			$n->condition = $this->ParenExpression($x);
			$x->stmtStack[] = $n;
			$n->thenPart = $this->Statement($x);
			$n->elsePart = $this->t->match(KEYWORD_ELSE) ? $this->Statement($x) : null;
			array_pop($x->stmtStack);

			return $n;
		case KEYWORD_SWITCH:
			$n = new JSNode($this->t);
			$this->t->mustMatch(OP_LEFT_PAREN);
			$n->discriminant = $this->Expression($x);
			$this->t->mustMatch(OP_RIGHT_PAREN);
			$n->cases = array();
			$n->defaultIndex = -1;

			$x->stmtStack[] = $n;

			$this->t->mustMatch(OP_LEFT_CURLY);

			while (($tt = $this->t->get()) !== OP_RIGHT_CURLY) {
				switch ($tt) {
					case KEYWORD_DEFAULT:
					$n2 = new JSNode($this->t);

						if ($n->defaultIndex !== -1) {
							throw $this->t->newSyntaxError('More than one switch default');
						}

						$n->defaultIndex = count($n->cases);
						break;
					case KEYWORD_CASE:
						$n2 = new JSNode($this->t);
						$n2->caseLabel = $this->Expression($x, OP_COLON);
						break;

					default:
						throw $this->t->newSyntaxError('Invalid switch case');
				}

				$this->t->mustMatch(OP_COLON);
				$n2->statements = new JSNode($this->t, JS_BLOCK);
				while (($tt = $this->t->peek()) !== KEYWORD_CASE && $tt !== KEYWORD_DEFAULT && $tt !== OP_RIGHT_CURLY) {
					$n2->statements->addNode($this->Statement($x));
				}

				$n->cases[] = $n2;
			}

			array_pop($x->stmtStack);
			return $n;
		case KEYWORD_FOR:
			$n = new JSNode($this->t);
			$n->isLoop = true;
			$this->t->mustMatch(OP_LEFT_PAREN);

			if (($tt = $this->t->peek()) !== OP_SEMICOLON) {
				$x->inForLoopInit = true;
				if ($tt === KEYWORD_VAR) {
					$this->t->get();
					$n2 = $this->Variables($x);
				} else {
					$n2 = $this->Expression($x);
				}

				$x->inForLoopInit = false;
			}

			if ($n2 && $this->t->match(KEYWORD_IN)) {
				$n->type = JS_FOR_IN;
				if ($n2->type === KEYWORD_VAR) {
					if (count($n2->nodes) !== 1) {
						throw $this->t->SyntaxError(
							'Invalid for..in left-hand side',
							$this->t->filename,
							$n2->lineno
						);
					}

					// NB: n2[0].type == IDENTIFIER and n2[0].value == n2[0].name.
					$n->iterator = $n2->nodes[0];
					$n->varDecl = $n2;
				} else {
					$n->iterator = $n2;
					$n->varDecl = null;
				}

				$n->object = $this->Expression($x);
			} else {
				$n->setup = $n2 ? $n2 : null;
				$this->t->mustMatch(OP_SEMICOLON);
				$n->condition = $this->t->peek() === OP_SEMICOLON ? null : $this->Expression($x);
				$this->t->mustMatch(OP_SEMICOLON);
				$n->update = $this->t->peek() === OP_RIGHT_PAREN ? null : $this->Expression($x);
			}

			$this->t->mustMatch(OP_RIGHT_PAREN);
			$n->body = $this->nest($x, $n);

			return $n;
		case KEYWORD_WHILE:
			$n = new JSNode($this->t);
			$n->isLoop = true;
			$n->condition = $this->ParenExpression($x);
			$n->body = $this->nest($x, $n);

			return $n;
		case KEYWORD_DO:
			$n = new JSNode($this->t);
			$n->isLoop = true;
			$n->body = $this->nest($x, $n, KEYWORD_WHILE);
			$n->condition = $this->ParenExpression($x);
			break;
		case KEYWORD_BREAK:
		case KEYWORD_CONTINUE:
			$n = new JSNode($this->t);

			if ($this->t->peekOnSameLine() === TOKEN_IDENTIFIER) {
				$this->t->get();
				$n->label = $this->t->currentToken()->value;
			}

			$ss = $x->stmtStack;
			$i = count($ss);
			$label = $n->label;
			if ($label) {
				do {
					if (--$i < 0) {
						throw $this->t->newSyntaxError('Label not found');
					}
				} while ($ss[$i]->label !== $label);
			} else {
				do {
					if (--$i < 0) {
						throw $this->t->newSyntaxError('Invalid ' . $tt);
					}
				} while (!$ss[$i]->isLoop && ($tt !== KEYWORD_BREAK || $ss[$i]->type !== KEYWORD_SWITCH));
			}

			$n->target = $ss[$i];
			break;
		case KEYWORD_TRY:
			$n = new JSNode($this->t);
			$n->tryBlock = $this->Block($x);

			if ($this->t->match(KEYWORD_CATCH)) {
				$n2 = new JSNode($this->t);
				$this->t->mustMatch(OP_LEFT_PAREN);
				$n2->varName = $this->t->mustMatch(TOKEN_IDENTIFIER)->value;

				$this->t->mustMatch(OP_RIGHT_PAREN);
				$n2->block = $this->Block($x);
				$n->catchClause = $n2;
			}

			if ($this->t->match(KEYWORD_FINALLY)) {
				$n->finallyBlock = $this->Block($x);
			}

			if (!$n->catchClause && !$n->finallyBlock) {
				throw $this->t->newSyntaxError('Invalid try statement');
			}

			return $n;
		case KEYWORD_CATCH:
		case KEYWORD_FINALLY:
			throw $this->t->newSyntaxError($tt + ' without preceding try');
		case KEYWORD_THROW:
			$n = new JSNode($this->t);
			$n->exception = $this->Expression($x);
			break;
		case KEYWORD_RETURN:
			if (!$x->inFunction) {
				throw $this->t->newSyntaxError('Invalid return');
			}

			$n = new JSNode($this->t);
			$tt = $this->t->peekOnSameLine();
			if ($tt !== TOKEN_END && $tt !== TOKEN_NEWLINE && $tt !== OP_SEMICOLON && $tt !== OP_RIGHT_CURLY) {
				$n->value = $this->Expression($x);
			} else {
				$n->value = null;
			}
		break;

		case KEYWORD_WITH:
			$n = new JSNode($this->t);
			$n->object = $this->ParenExpression($x);
			$n->body = $this->nest($x, $n);
		return $n;

		case KEYWORD_VAR:
			$n = $this->Variables($x);
		break;

		case TOKEN_CONDCOMMENT_START:
		case TOKEN_CONDCOMMENT_END:
			$n = new JSNode($this->t);
		return $n;

		case KEYWORD_DEBUGGER:
			$n = new JSNode($this->t);
		break;

		case TOKEN_NEWLINE:
		case OP_SEMICOLON:
			$n = new JSNode($this->t, OP_SEMICOLON);
			$n->expression = null;
			return $n;

		default:
			if ($tt === TOKEN_IDENTIFIER) {
				$this->t->scanOperand = false;
				$tt = $this->t->peek();
				$this->t->scanOperand = true;
				if ($tt === OP_COLON) {
					$label = $this->t->currentToken()->value;
					$ss = $x->stmtStack;
					for ($i = count($ss) - 1; $i >= 0; --$i) {
						if ($ss[$i]->label === $label) {
							throw $this->t->newSyntaxError('Duplicate label');
						}
					}

					$this->t->get();
					$n = new JSNode($this->t, JS_LABEL);
					$n->label = $label;
					$n->statement = $this->nest($x, $n);

					return $n;
				}
			}

			$n = new JSNode($this->t, OP_SEMICOLON);
			$this->t->unget();
			$n->expression = $this->Expression($x);
			$n->end = $n->expression->end;

			break;
		}

		if ($this->t->lineno === $this->t->currentToken()->lineno) {
			$tt = $this->t->peekOnSameLine();
			if ($tt !== TOKEN_END && $tt !== TOKEN_NEWLINE && $tt !== OP_SEMICOLON && $tt !== OP_RIGHT_CURLY) {
				throw $this->t->newSyntaxError('Missing ; before statement');
			}
		}

		$this->t->match(OP_SEMICOLON);

		return $n;
	}

	private function FunctionDefinition(JSCompilerContext $x, $requireName, $functionForm) {
		$f = new JSNode($this->t);

		if ($f->type !== KEYWORD_FUNCTION) {
			throw $this->t->newSyntaxError('Invalid function definition');
		}

		if ($this->t->match(TOKEN_IDENTIFIER)) {
			$f->name = $this->t->currentToken()->value;
		} elseif ($requireName) {
			throw $this->t->newSyntaxError('Missing function identifier');
		}

		$this->t->mustMatch(OP_LEFT_PAREN);
		$f->params = array();

		while (($tt = $this->t->get()) !== OP_RIGHT_PAREN) {
			if ($tt !== TOKEN_IDENTIFIER) {
				throw $this->t->newSyntaxError('Missing formal parameter');
			}

			$f->params[] = $this->t->currentToken()->value;

			if ($this->t->peek() !== OP_RIGHT_PAREN) {
				$this->t->mustMatch(OP_COMMA);
			}
		}

		$this->t->mustMatch(OP_LEFT_CURLY);

		$x2 = new JSCompilerContext(true, $x);
		$f->body = $this->Script($x2);

		$this->t->mustMatch(OP_RIGHT_CURLY);
		$f->end = $this->t->currentToken()->end;

		$f->functionForm = $functionForm;
		if ($functionForm === DECLARED_FORM) {
			$x->funDecls[] = $f;
		}

		return $f;
	}

	private function Variables($x) {
		$n = new JSNode($this->t);

		do {
			$this->t->mustMatch(TOKEN_IDENTIFIER);

			$n2 = new JSNode($this->t);
			$n2->name = $n2->value;

			if ($this->t->match(OP_ASSIGN)) {
				if ($this->t->currentToken()->assignOp) {
					throw $this->t->newSyntaxError('Invalid variable initialization');
				}

				$n2->initializer = $this->Expression($x, OP_COMMA);
			}

			$n2->readOnly = false;

			$n->addNode($n2);
			$x->varDecls[] = $n2->name;
		} while ($this->t->match(OP_COMMA));

		return $n;
	}

	private function Expression($x, $stop=false) {
		$operators = array();
		$operands = array();
		$n = false;

		$bl = $x->bracketLevel;
		$cl = $x->curlyLevel;
		$pl = $x->parenLevel;
		$hl = $x->hookLevel;

		while (($tt = $this->t->get()) !== TOKEN_END) {
			if ($tt === $stop && $x->bracketLevel === $bl && $x->curlyLevel === $cl && $x->parenLevel === $pl && $x->hookLevel === $hl) {
				break;
			}

			switch ($tt) {
			case OP_SEMICOLON:
				break 2;
			case OP_HOOK:
				if ($this->t->scanOperand) {
					break 2;
				}

				while (!empty($operators) && $this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt]) {
					$this->reduce($operators, $operands);
				}

				$operators[] = new JSNode($this->t);

				++$x->hookLevel;
				$this->t->scanOperand = true;
				$n = $this->Expression($x);

				if (!$this->t->match(OP_COLON)) {
					break 2;
				}

				--$x->hookLevel;
				$operands[] = $n;
				break;
			case OP_COLON:
				if ($x->hookLevel) {
					break 2;
				}

				throw $this->t->newSyntaxError('Invalid label');
				break;
			case OP_ASSIGN:
				if ($this->t->scanOperand) {
					break 2;
				}

				while ($operators && $this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt]) {
					$this->reduce($operators, $operands);
				}

				$operators[] = new JSNode($this->t);
				end($operands)->assignOp = $this->t->currentToken()->assignOp;
				$this->t->scanOperand = true;
				break;
			case KEYWORD_IN:
				if ($x->inForLoopInit && !$x->hookLevel && !$x->bracketLevel && !$x->curlyLevel && !$x->parenLevel) {
					break 2;
				}
			case OP_COMMA:
				if ($tt === OP_COMMA && $x->hookLevel && !$x->bracketLevel && !$x->curlyLevel && !$x->parenLevel) {
					break 2;
				}
			case OP_OR:
			case OP_AND:
			case OP_BITWISE_OR:
			case OP_BITWISE_XOR:
			case OP_BITWISE_AND:
			case OP_EQ: case OP_NE: case OP_STRICT_EQ: case OP_STRICT_NE:
			case OP_LT: case OP_LE: case OP_GE: case OP_GT:
			case KEYWORD_INSTANCEOF:
			case OP_LSH: case OP_RSH: case OP_URSH:
			case OP_PLUS: case OP_MINUS:
			case OP_MUL: case OP_DIV: case OP_MOD:
			case OP_DOT:
				if ($this->t->scanOperand) {
					break 2;
				}

				while (!empty($operators) && $this->opPrecedence[end($operators)->type] >= $this->opPrecedence[$tt]) {
					$this->reduce($operators, $operands);
				}

				if ($tt === OP_DOT) {
					$this->t->mustMatch(TOKEN_IDENTIFIER);
					$operands[] = new JSNode($this->t, OP_DOT, array_pop($operands), new JSNode($this->t));
				} else {
					$operators[] = new JSNode($this->t);
					$this->t->scanOperand = true;
				}
				break;
			case KEYWORD_DELETE: case KEYWORD_VOID: case KEYWORD_TYPEOF:
			case OP_NOT: case OP_BITWISE_NOT: case OP_UNARY_PLUS: case OP_UNARY_MINUS:
			case KEYWORD_NEW:
				if (!$this->t->scanOperand) {
					break 2;
				}

				$operators[] = new JSNode($this->t);
				break;
			case OP_INCREMENT: case OP_DECREMENT:
				if ($this->t->scanOperand) {
					$operators[] = new JSNode($this->t);
				} else {
					$t = $this->t->tokens[($this->t->tokenIndex + $this->t->lookahead - 1) & 3];

					if ($t && $t->lineno !== $this->t->lineno) {
						break 2;
					}

					while ($operators && $this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt]) {
						$this->reduce($operators, $operands);
					}

					$n = new JSNode($this->t, $tt, array_pop($operands));
					$n->postfix = true;
					$operands[] = $n;
				}
				break;
			case KEYWORD_FUNCTION:
				if (!$this->t->scanOperand) {
					break 2;
				}

				$operands[] = $this->FunctionDefinition($x, false, EXPRESSED_FORM);
				$this->t->scanOperand = false;

				break;
			case KEYWORD_NULL: case KEYWORD_THIS: case KEYWORD_TRUE: case KEYWORD_FALSE:
			case TOKEN_IDENTIFIER: case TOKEN_NUMBER: case TOKEN_STRING: case TOKEN_REGEXP:
				if (!$this->t->scanOperand) {
					break 2;
				}

				$operands[] = new JSNode($this->t);
				$this->t->scanOperand = false;
				break;
			case TOKEN_CONDCOMMENT_START:
			case TOKEN_CONDCOMMENT_END:
				if ($this->t->scanOperand) {
					$operators[] = new JSNode($this->t);
				} else {
					$operands[] = new JSNode($this->t);
				}

				break;
			case OP_LEFT_BRACKET:
				if ($this->t->scanOperand) {
					$n = new JSNode($this->t, JS_ARRAY_INIT);
					while (($tt = $this->t->peek()) !== OP_RIGHT_BRACKET) {
						if ($this->t->peek() === OP_COMMA) {
							$n->addNode(new JSNode(TOKEN_UNDEFINED));
						} else {
							$n->addNode($this->Expression($x, OP_COMMA));
						}

						if (!$this->t->match(OP_COMMA)) {
							break;
						}
					}

					$this->t->mustMatch(OP_RIGHT_BRACKET);
					$operands[] = $n;
					$this->t->scanOperand = false;
				} else {
					$operators[] = new JSNode($this->t, JS_INDEX);
					$this->t->scanOperand = true;
					++$x->bracketLevel;
				}

				break;
			case OP_RIGHT_BRACKET:
				if ($this->t->scanOperand || $x->bracketLevel === $bl) {
					break 2;
				}

				while ($this->reduce($operators, $operands)->type !== JS_INDEX);

				--$x->bracketLevel;

				break;
			case OP_LEFT_CURLY:
				if (!$this->t->scanOperand) {
					break 2;
				}

				++$x->curlyLevel;
				$n = new JSNode($this->t, JS_OBJECT_INIT);
				while (!$this->t->match(OP_RIGHT_CURLY)) {
					do {
						$tt = $this->t->get();
						$tv = $this->t->currentToken()->value;

						switch ($tt) {
						case TOKEN_IDENTIFIER:
						case TOKEN_NUMBER:
						case TOKEN_STRING:
							$id = new JSNode($this->t);
							break;
						case OP_RIGHT_CURLY:
							//throw $this->t->newSyntaxError('Illegal trailing ,');
							// instead of the error, close it
							break 3;
						default:
							throw $this->t->newSyntaxError('Invalid property name');
						}

						$this->t->mustMatch(OP_COLON);
						$n->addNode(new JSNode($this->t, JS_PROPERTY_INIT, $id, $this->Expression($x, OP_COMMA)));
					} while ($this->t->match(OP_COMMA));

					$this->t->mustMatch(OP_RIGHT_CURLY);
					break;
				}

				$operands[] = $n;
				$this->t->scanOperand = false;
				--$x->curlyLevel;

				break;
			case OP_RIGHT_CURLY:
				if (!$this->t->scanOperand && $x->curlyLevel !== $cl) {
					throw new Exception('PANIC: right curly botch');
				}
				break 2;
			case OP_LEFT_PAREN:
				if ($this->t->scanOperand) {
					$operators[] = new JSNode($this->t, JS_GROUP);
				} else {
					while ($operators && $this->opPrecedence[end($operators)->type] > $this->opPrecedence[KEYWORD_NEW]) {
						$this->reduce($operators, $operands);
					}

					$n = end($operators);
					$this->t->scanOperand = true;
					if ($this->t->match(OP_RIGHT_PAREN)) {
						if ($n && $n->type === KEYWORD_NEW) {
							array_pop($operators);
							$n->addNode(array_pop($operands));
						} else {
							$n = new JSNode($this->t, JS_CALL, array_pop($operands), new JSNode($this->t, JS_LIST));
						}

						$operands[] = $n;
						$this->t->scanOperand = false;
						break;
					}

					if ($n && $n->type === KEYWORD_NEW) {
						$n->type = JS_NEW_WITH_ARGS;
					} else {
						$operators[] = new JSNode($this->t, JS_CALL);
					}
				}

				++$x->parenLevel;
				break;
			case OP_RIGHT_PAREN:
				if ($this->t->scanOperand || $x->parenLevel === $pl) {
					break 2;
				}

				while (($tt = $this->reduce($operators, $operands)->type) !== JS_GROUP && $tt !== JS_CALL && $tt !== JS_NEW_WITH_ARGS) {}

				if ($tt !== JS_GROUP) {
					$n = end($operands);
					if ($n->nodes[1]->type !== OP_COMMA) {
						$n->nodes[1] = new JSNode($this->t, JS_LIST, $n->nodes[1]);
					} else {
						$n->nodes[1]->type = JS_LIST;
					}
				}

				--$x->parenLevel;
				break;
			default:
				break 2;
			}
		}

		if ($x->hookLevel !== $hl) {
			throw $this->t->newSyntaxError('Missing : in conditional expression');
		}

		if ($x->parenLevel !== $pl) {
			throw $this->t->newSyntaxError('Missing ) in parenthetical');
		}

		if ($x->bracketLevel !== $bl) {
			throw $this->t->newSyntaxError('Missing ] in index expression');
		}

		if ($this->t->scanOperand) {
			throw $this->t->newSyntaxError('Missing operand');
		}

		// Resume default mode, scanning for operands, not operators.
		$this->t->scanOperand = true;
		$this->t->unget();

		while ($operators) {
			$this->reduce($operators, $operands);
		}

		return array_pop($operands);
	}

	private function ParenExpression($x) {
		$this->t->mustMatch(OP_LEFT_PAREN);
		$n = $this->Expression($x);
		$this->t->mustMatch(OP_RIGHT_PAREN);

		return $n;
	}

	// Statement stack and nested statement handler.
	private function nest($x, $node, $end = false) {
		$x->stmtStack[] = $node;
		$n = $this->statement($x);
		array_pop($x->stmtStack);

		if ($end) {
			$this->t->mustMatch($end);
		}

		return $n;
	}

	private function reduce(&$operators, &$operands) {
		$n = array_pop($operators);
		$op = $n->type;
		$arity = $this->opArity[$op];
		$c = count($operands);
		if ($arity === -2) {
			if ($c >= 2) {
				$left = $operands[$c - 2];
				if ($left->type === $op) {
					$left->addNode(array_pop($operands));

					return $left;
				}
			}

			$arity = 2;
		}

		// Always use push to add operands to n, to update start and end
		$a = array_splice($operands, $c - $arity);
		for ($i = 0; $i < $arity; ++$i) {
			$n->addNode($a[$i]);
		}

		$operands[] = $n;

		return $n;
	}
}

class JSCompilerContext {
	protected $parent;

	public $inFunction = false;
	public $inForLoopInit = false;
	public $ecmaStrictMode = false;
	public $bracketLevel = 0;
	public $curlyLevel = 0;
	public $parenLevel = 0;
	public $hookLevel = 0;

	public $stmtStack = array();
	public $funDecls = array();
	public $varDecls = array();

	public function __construct($inFunction, JSCompilerContext $parent = null) {
		$this->inFunction = $inFunction;
	}
}

class JSNode {
	public $type;
	public $value;

	public $data = array();
	public $parent;

	public $nodes = array();
	public $funDecls = array();
	public $varDecls = array();

	public function __construct($t, $type = 0) {
		if( is_object( $t ) ) {
			if ($token = $t->currentToken()) {
				$this->type = $type ? $type : $token->type;
				$this->value = $token->value;
			} else {
				$this->type = $type;
			}
		} else {
			$this->type = $t;
		}

		if (func_num_args() > 2) {
			foreach(array_slice(func_get_args(), 2) as $arg) {
				if( $arg !== null ) {
					$this->addNode($arg);
				}
			}
		}
	}

	// we don't want to bloat our object with all kind of specific properties, so we use overloading
	public function __set($name, $value) {
		$this->$name = $value;
	}

	public function __isset($name) {
		return false;
	}

	public function __get($name) {
		return null;
	}

	public function addNode($node) {
		$this->nodes[] = $node;
	}
}
