<?php
class BlockStatement extends Node {
	public function __construct(array $nodes) {
		$this->nodes = $nodes;
		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$revisit = false;

		$nodes = $this->redoIfElse($this->nodes);
		$nodes = $this->mergeBlocks($nodes);
		$nodes = $this->moveExpressions($nodes);
		$nodes = $this->transformToComma($ast, $nodes, $revisit);
		$nodes = $this->moveVars($nodes);

		$count = count($nodes);

		if ($count === 0) {
			return new VoidExpression(new Number(0));
		}

		if ($count === 1) {
			return $nodes[0];
		}

		if ($this instanceof BlockStatement && $count === 2 && $nodes[0] instanceof Expression && $nodes[1] instanceof ReturnNode) {
			if ($nodes[1]->moveExpression($nodes[0])) {
				$nodes = array($nodes[1]);
				$count = 1;
			}
		}

		$result = new BlockStatement($nodes);

		if ($revisit) {
			return $result->visit($ast, $parent);
		}

		return $result;
	}

	public function add(Node $n) {
		$this->nodes[] = $n;
	}

	protected function moveExpressions(array $original) {
		$nodes = array();
		$last = null;

		foreach ($original as $n) {
			if ($last) {
				if (!$n->moveExpression($last)) {
					$nodes[] = $last;
				}

				$last = null;
			}

			if ($n instanceof Expression) {
				$last = $n;
			} else {
				$nodes[] = $n;
			}
		}

		if ($last) {
			$nodes[] = $last;
		}

		return $nodes;
	}

	protected function transformToComma(AST $ast, array $original, &$revisit) {
		$nodes = array();
		$last = null;

		foreach ($original as $n) {
			foreach($n->visit($ast, $this)->optimize()->removeUseless()->nodes() as $x) {
				foreach($x->breaking() ?: array($x) as $x) {
					if ($x->isConstant()) {
						$x->gone();
						continue;
					}

					if ($last instanceof Expression && $x instanceof Expression) {
						$last = new CommaExpression(array_merge($last->nodes(), $x->nodes()));
						array_splice($nodes, -1, 1, array($last));
					} else {
						$nodes[] = $x;
						$last = $x;
					}
				}
			}
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
			foreach($n instanceof BlockStatement ? $n->nodes : array($n) as $x) {
				$nodes[] = $x;
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

	protected function moveVars(array $original) {
		$vars = array();
		$nodes = array();

		foreach($original as $n) {
			if ($n instanceof VarNode) {
				$vars[] = $n;
			} else {
				$finished = false;
				if ($n instanceof ForNode) {
					if ($vars) {
						if (!$n->initializer() || $n->initializer()->isVoid()) {
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
			if ($forceNoBraces === false) {
				return '{}';
			} elseif ($forceOut) {
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

			$x = $n->toString(false);

			if ((string)$x === '') {
				continue;
			}

			$f = $n->first();

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

		if (AST::$options['beautify']) {
			$o = "\n" . preg_replace('~^(?!$)~m', '    ', $o) . "\n";

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
			if (end($this->nodes)->optimizeBreak()->isVoid()) {
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

	public function countLetters(&$letters) {
		foreach ($this->nodes as $n) {
			$n->countLetters($letters);
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
