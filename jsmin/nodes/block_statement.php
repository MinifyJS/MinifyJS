<?php
class BlockStatement extends Node {
	public function __construct(array $nodes) {
		$this->nodes = $nodes;
		parent::__construct();
	}

	public function visit(AST $ast) {
		foreach($this->nodes as $i => $n) {
			$this->nodes[$i] = $n->visit($ast);
		}

		// omzetten naar comma-statement
		$list = array();
		$nodes = array();

		foreach($this->nodes as $n) {
			if ($n instanceof Expression) {
				if ($n instanceof CommaExpression) {
					foreach($n->nodes as $x) {
						if (!$x->isVoid()) {
							$list[] = $x;
						}
					}
				} elseif (!$n->isVoid()) {
					$list[] = $n;
				}
			} else {
				if ($list) {
					if (count($list) === 1) {
						$nodes[] = $list[0];
					} else {
						$nodes[] = new CommaExpression($list);
					}

					$list = array();
				}

				$nodes[] = $n;
			}
		}

		if ($list) {
			if (count($list) === 1) {
				$nodes[] = $list[0];
			} else {
				$nodes[] = new CommaExpression($list);
			}

			$list = array();
		}

		if (count($nodes) === 1 && $nodes[0] instanceof Expression) {
			return $nodes[0];
		}

		if (count($nodes) === 0) {
			return new VoidExpression(new Number(0));
		}

		$this->nodes = $nodes;

		return $this;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $n) {
			$n->collectStatistics($ast);
		}
	}

	public function toString($forceNoBraces = null, $forceOut = false) {
		if (count($this->nodes) === 1 && $this->nodes[0] instanceof BlockStatement) {
			return $this->nodes[0]->toString($forceNoBraces);
		} elseif (!$this->nodes) {
			if ($forceOut) {
				return ";\0";
			} else {
				return ';';
			}
		}

		$o = array();
		$varCache = array();
		foreach($this->nodes as $n) {
			if (!$n || $n->isConstant()) {
				continue;
			}

			if ($varCache && !$n instanceof VarNode) {
				$o[] = 'var ' . implode(',', $varCache) . ';';
				$varCache = array();
			}

			if ($n instanceof VarNode) {
				$a = $n->toString();

				if ($a !== '') {
					$varCache[] = substr($a, 4);
				}

				continue;
			}

			$f = $n->first();

			$x = $n->toString();

			/*
			 * ECMA-262, 12.4 Expression Statement
			 * 	ExpressionStatement :
			 * 	[lookahead notin ({, function)] Expression ;
			 *
			 * 	Note that an ExpressionStatement cannot start with an opening curly brace because that might make it
			 * 	ambiguous with a Block. Also, an ExpressionStatement cannot start with the function keyword because
			 * 	that might make it ambiguous with a FunctionDeclaration.
			 *
			 * Enfin, when a statements first thing is one of these, it needs parens
			 */
			if ($f instanceof FunctionExpression || $f instanceof ObjectExpression) {
				$x = '(' . $x . ')';
			}

			if ($n instanceof Expression || $n instanceof VarNode || $n instanceof ReturnNode
					|| $n instanceof BreakNode || $n instanceof ContinueNode || $n instanceof ThrowNode) {
				$x .= ';';
			}

			$o[] = $x;
		}

		if ($varCache && !$n instanceof VarNode) {
			$o[] = 'var ' . implode(',', $varCache) . ';';
			$varCache = array();
		}

		$size = count($o);

		$o = implode('', $o);

		if ($forceNoBraces === null && $size > 1 || $forceNoBraces === false) {
			$o = '{' . Stream::trimSemicolon($o) . '}';
		}

		if ($o === '' && $forceOut) {
			return ";\0";
		}

		return $o;
	}

	public function last() {
		return end($this->nodes);
	}

	public function isSingle() {
		return count($this->nodes) === 1 ? $this->nodes[0] : null;
	}
}
