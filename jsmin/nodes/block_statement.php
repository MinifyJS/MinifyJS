<?php
class BlockStatement extends Node {
	public function __construct(array $nodes) {
		$this->nodes = $nodes;
		parent::__construct();
	}

	public function visit(AST $ast) {
		// omzetten naar comma-statement
		$list = array();
		$nodes = array();

		$revisit = false;

		foreach($this->nodes as $n) {
			$n = $n->visit($ast);

			if ($n instanceof Expression) {
				$n = $n->removeUseless();

				if ($n instanceof CommaExpression) {
					foreach($n->nodes as $x) {
						$x = $x->removeUseless();
						if (!$x->isVoid()) {
							$list[] = $x;
						}
					}
				} else {
					$x = $n->removeUseless();
					if (!$x->isVoid()) {
						$list[] = $x;
					}
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

				// okay… let's get smart!
				if ($tune = $n->breaking()) {
					foreach($tune as $x) {
						$nodes[] = $x;

						$revisit = true;
					}
				} else {
					$nodes[] = $n;
				}
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

		if (($c = count($nodes)) === 1) {
			return $nodes[0];
		}

		if ($c === 0) {
			return new VoidExpression(new Number(0));
		}

		// simple optimization, a,b,c;return d; -> return a,b,c,d; (comma operator)
		if ($c === 2 && $nodes[0] instanceof Expression && $nodes[1] instanceof ReturnNode && $nodes[1]->value()) {
			// double comma operators get fixed
			$result = new ReturnNode(new CommaExpression(array($nodes[0], $nodes[1]->value())));
			$revisit = true;
		} else {
			$result = $this->reverseIfElse($nodes, $revisit);
		}

		if ($revisit) {
			return $result->visit($ast);
		}

		return $result;
	}

	protected function reverseIfElse(array $nodes, &$revisit) {
		// we will loop, if we find if(...) return; ... , transform into if(!...) { ... }
		$add = $base = new BlockStatement(array());

		foreach($nodes as $node) {
			if ($node instanceof IfNode && !$node->_else() && $node->then() instanceof ReturnNode && !$node->then()->value()) {
				// got one!
				$old = $add;
				$add = new BlockStatement(array());

				$old->add(new IfNode($node->condition()->negate(), $add));

				$revisit = true;
			} else {
				$add->add($node);
			}
		}

		return $base;
	}

	public function add(Node $n) {
		$this->nodes[] = $n;
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
			if (!$n || $n->isConstant() || $n->isVoid()) {
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
		} elseif ($forceNoBraces === true) {
			$o = Stream::trimSemicolon($o);
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

	public function breaking() {
		$nodes = array();
		$broken = false;

		foreach($this->nodes as $node) {
			$nodes[] = $node;

			if ($node->isBreaking()) {
				$broken = true;
				break;
			}
		}

		return $broken ? $nodes : null;
	}
}
