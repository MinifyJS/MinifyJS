<?php
class BlockStatement extends Node {
	public function __construct(array $nodes) {
		$this->nodes = $nodes;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$revisit = false;

		$count = count($nodes = $this->moveVars(
			$this->transformToComma(
				$ast,
				$this->mergeBlocks($this->redoIfElse($this->nodes)),
				$revisit
			),
			$revisit
		));

		if ($count === 0) {
			return new VoidExpression(new Number(0));
		}

		if ($count >= 2) {
			$expr = $nodes[$count - 2];
			$return = $nodes[$count - 1];

			if ($expr instanceof Expression && $return instanceof ReturnNode && !$return->value()->isVoid()) {
				array_splice($nodes, -2, 2, array(
					new ReturnNode(new CommaExpression(array_merge(
						$expr->nodes(),
						$return->value()->nodes()
					)))
				));

				--$count;
				$revisit = true;
			}
		}

		if ($count === 1) {
			if ($revisit) {
				return $nodes[0]->visit($ast);
			}

			return $nodes[0];
		}

		$result = new BlockStatement($nodes);

		if ($revisit) {
			return $result->visit($ast);
		}

		return $result;
	}

	public function add(Node $n) {
		$this->nodes[] = $n;
	}

	protected function transformToComma(AST $ast, array $original, &$revisit) {
		$list = array();
		$nodes = array();
		$vars = array();

		$returnSeen = false;

		foreach($original as $n) {
			// removed functions always need revisiting
			$wasFunction = $n instanceof FunctionNode;

			$n = $n->visit($ast, $this)->optimize();

			if ($wasFunction && !($n instanceof FunctionNode)) {
				$revisit = true;
			}

			if ((!$returnSeen && !$n->isConstant()) || $n instanceof VarNode || $n instanceof FunctionNode) {
				if ($n instanceof Expression) {
					foreach($n->removeUseless()->nodes() as $x) {
						$x = $x->removeUseless();
						if (!$x->isVoid()) {
							if ($x instanceof AssignExpression && $x->assignType() === '='
									&& $x->left() instanceof IdentifierExpression && $x->right()->mayInline()
									&& isset($vars[$x->left()->value()])) {
								$vars[$x->left()->value()]->initializer($x->right());
								$x->left()->gone();
							} else {
								$vars = array();
								$list[] = $x;
							}
						}
					}
				} else {
					if ($list) {
						$nodes[] = count($list) === 1 ? $list[0] : new CommaExpression($list);
						$list = array();
					}

					// okay… let's get smart!
					foreach(($n->breaking() ?: array($n)) as $x) {
						if (!$returnSeen || $x instanceof VarNode || $x instanceof FunctionNode) {
							if ($x instanceof ReturnNode || $x instanceof BreakNode) {
								$returnSeen = true;
							}

							if ($x instanceof VarNode && (!$x->initializer() || $x->initializer()->isVoid())) {
								$vars[$x->name()->value()] = $x;
							}

							$nodes[] = $x;
						} else {
							$x->gone();
							$revisit = true;
						}
					}
				}
			} else {
				$n->gone();
			}
		}

		if ($list) {
			$nodes[] = count($list) === 1 ? $list[0] : new CommaExpression($list);
		}

		return $nodes;
	}

	public function removeBreak() {
		if (($last = end($this->nodes)) instanceof BreakNode && !$last->hasLabel()) {
			array_pop($this->nodes);
		}

		return $this;
	}

	protected function mergeBlocks(array $original) {
		$nodes = array();

		foreach($original as $n) {
			if ($n instanceof BlockStatement) {
				foreach($n->nodes as $x) {
					$nodes[] = $x;
				}
			} else {
				$nodes[] = $n;
			}
		}

		return $nodes;
	}

	protected function redoIfElse(array $nodes) {
		for ($i = 0, $length = count($nodes); $i < $length; ++$i) {
			$n = $nodes[$i];

			if ($n instanceof IfNode && !$n->_else()) {
				if (($l = $n->then()->last()) && $l->isBreaking()) {
					$r = array_slice($nodes, $i + 1);

					if (!$r) {
						continue;
					}

					$e = $this->redoIfElse($r);

					return array_merge(
						array_slice($nodes, 0, $i),
						array(new IfNode(
							$n->condition(),
							$n->then(),
							count($e) === 1 ? $e[0] : new BlockStatement($e)
						))
					);
				}
			}
		}

		return $nodes;
	}

	protected function moveVars(array $original, &$revisit) {
		$vars = array();
		$nodes = array();

		foreach($original as $n) {
			if ($n instanceof VarNode) {
				$vars[] = $n;
			} else {
				$finished = false;
				if ($n instanceof ForNode) {
					if ($vars && $n->initializer()) {
						if ($n->initializer()->isVoid()) {
							$n->initializer(new VarDeclarationsNode($vars));
							$finished = true;
						} elseif ($n->initializer() instanceof VarDeclarationsNode) {
							$var = new VarDeclarationsNode($vars);
							$finished = $var->merge($n->initializer());
							$var = null;
						}
					}
				} elseif ($n instanceof ForInNode && count($vars) === 1) {
					$iter = $n->iterator();
					if (($iter instanceof IdentifierExpression) && ($iter->value() === $vars[0]->name()->value())) {
						$n->iterator($vars[0]);
						$finished = true;
					}
				}

				if ($vars) {
					if (!$finished) {
						foreach($vars as $var) {
							$nodes[] = $var;
						}
					}

					$vars = array();
				}

				$nodes[] = $n;
			}
		}

		if ($vars) {
			foreach($vars as $var) {
				$nodes[] = $var;
			}
		}

		return $nodes;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $n) {
			$n->collectStatistics($ast);
		}
	}

	public function toString($forceNoBraces = null, $forceOut = true) {
		if (count($this->nodes) === 1 && $this->nodes[0] instanceof BlockStatement) {
			return $this->nodes[0]->toString($forceNoBraces, $forceOut);
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

			if ($n instanceof VarNode) {
				$a = $n->toString();

				if ($a !== '') {
					$varCache[] = substr($a, 4);
				}

				continue;
			} elseif ($varCache) {
				if (AST::$options['beautify']) {
					$o[] = 'var ' . ltrim(Stream::indent(implode(",\n", $varCache))) . ';';
				} else {
					$o[] = 'var ' . implode(',', $varCache) . ';';
				}

				$varCache = array();
			}

			$f = $n->first();

			$x = $n->toString(false);

			if (!strlen($x)) {
				continue;
			}

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

			$focus = $n instanceof LabelNode ? $n->stmt() : $n;

			if ($focus instanceof Expression || $focus instanceof VarNode || $focus instanceof ReturnNode
					|| $focus instanceof BreakNode || $focus instanceof ContinueNode || $focus instanceof ThrowNode
					|| $focus instanceof DebuggerNode) {
				$x .= ';';
			}

			$o[] = $x;
		}

		if ($varCache) {
			if (AST::$options['beautify']) {
				$o[] = 'var ' . ltrim(Stream::indent(implode(",\n", $varCache))) . ';';
			} else {
				$o[] = 'var ' . implode(',', $varCache) . ';';
			}

			$varCache = array();
		}

		$size = count($o);

		$o = implode(AST::$options['beautify'] ? "\n" : '', $o);

		if (AST::$options['beautify'] && ($size > 1 || $forceNoBraces === false)) {
			$o = "\n" . preg_replace('~^~m', '    ', $o) . "\n";

			if ($forceNoBraces !== true) {
				$o = '{' . $o . '}';
			}
		} else {
			if ($forceNoBraces === null && $size > 1 || $forceNoBraces === false) {
				$o = '{' . Stream::trimSemicolon($o) . '}';
			} elseif ($forceNoBraces === true) {
				$o = Stream::trimSemicolon($o);
			}
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
		//return array();

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

	public function nodes() {
		return $this->nodes;
	}

	public function optimizeBreak() {
		if ($this->nodes) {
			$check = end($this->nodes)->optimizeBreak();
			if ($check instanceof ContinueNode && !$check->hasLabel()) {
				array_splice($this->nodes, -1);
			}
		}

		if (!$this->nodes) {
			return new VoidExpression(new Number(0));
		}

		return $this;
	}

	public function gone() {
		foreach($this->nodes as $n) {
			$n->gone();
		}
	}

	public function declarations() {
		$decls = array();

		foreach($this->nodes as $n) {
			foreach($n->declarations() as $x) {
				$decls[] = $x;
			}
		}
	}

	public function debug() {
		$out = array();
		foreach($this->nodes as $n) {
			$out[] = $n->debug();
		}

		return "{\n" . preg_replace('~^~m', '    ', implode("\n", $out)) . "\n}";
	}
}
