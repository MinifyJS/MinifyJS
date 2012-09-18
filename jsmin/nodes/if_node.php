<?php
class IfNode extends Node {
	protected $condition;
	protected $then;

	protected $else;

	public function __construct(Expression $cond, Node $then, Node $else = null) {
		$this->condition = $cond;
		$this->then = $then->asBlock();
		$this->else = $else ? $else->asBlock() : null;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$condition = $this->condition->visit($ast);

		$this->condition = AST::bestOption(array(
			$condition->negate()->negate()->looseBoolean(),
			$condition->looseBoolean()
		));

		$this->then = $this->then->visit($ast);

		if ($this->else) {
			$this->else = $this->else->visit($ast);
		}

		// if (*) {} else *; -> if (!*) *
		if ($this->else && !$this->else->isVoid() && $this->then->isVoid()) {
			$this->condition = $this->condition->negate()->looseBoolean();
			$this->then = $this->else;
			$this->else = null;
		}

		if (!$this->else || $this->else->isVoid()) {
			while ($this->then instanceof AndExpression) {
				$this->condition = new AndExpression($this->condition, $this->then->left());
				$this->then = $this->then->right();
			}
		}

		if ($this->then->isVoid() && (!$this->else || $this->else->isVoid())) {
			return $this->condition;
		}

		$result = null;

		if ($this->then && $this->else) {
			// if (a) f(); else g(); -> a?f():g()
			if ($this->then instanceof Expression && $this->else instanceof Expression) {
				$result = new HookExpression(
					$this->condition,
					$this->then,
					$this->else
				);
			} elseif (($this->then instanceof ReturnNode && $this->else instanceof ReturnNode)
					|| ($this->then instanceof ThrowNode && $this->else instanceof ThrowNode)) {
				if ($this->then->value() && $this->else->value()) {
					$class = $this->then instanceof ReturnNode ? 'ReturnNode' : 'ThrowNode';

					$result = new $class(new HookExpression(
						$this->condition,
						$this->then->value(),
						$this->else->value()
					));
				}
			} else {
				$option = new IfNode($this->condition->negate(), $this->else, $this->then);

				if (strlen($option->toString()) < strlen($this->toString())) {
					$result = $option;
				}
			}
		} elseif (!$this->else && $this->then instanceof Expression) {
			$result = new AndExpression($this->condition, $this->then);
		} elseif (!$this->else && (null !== $cond = $this->condition->asBoolean())) {
			if ($cond) {
				return $this->then;
			}

			return new VoidExpression(new Number(0));
		} elseif ($this->then instanceof IfNode && (!$this->else || $this->else->isVoid()) && (!$this->then->else || $this->then->else->isVoid())) {
			$result = new IfNode(
				new AndExpression($this->condition, $this->then->condition),
				$this->then->then
			);
		}

		return $result ? $result->visit($ast) : $this;
	}

	public function collectStatistics(AST $ast) {
		$this->condition->collectStatistics($ast);
		$this->then->collectStatistics($ast);

		if ($this->else) {
			$this->else->collectStatistics($ast);
		}
	}

	public function optimizeBreak() {
		$then = $this->then->optimizeBreak();
		$else = $this->else ? $this->else->optimizeBreak() : null;

		if ($else && $else->isVoid()) {
			$else = null;
		}

		return new IfNode($this->condition, $then, $else);
	}

	public function gone() {
		$this->condition->gone();

		if ($this->then) {
			$this->then->gone();
		}

		if ($this->else) {
			$this->else->gone();
		}
	}

	public function toString() {
		$space = AST::$options['beautify'] ? ' ' : '';
		$noBlock = null;

		if ($this->else && !($this->then instanceof Expression || $this->then instanceof VarNode || $this->then instanceof ReturnNode
				|| $this->then instanceof BreakNode || $this->then instanceof ContinueNode || $this->then instanceof ThrowNode
				|| $this->then instanceof DebuggerNode)) {
			$noBlock = false;
		}

		$o = 'if' . $space . '(' . $this->condition->toString() . ')' . $space . $this->then->asBlock()->toString($noBlock, true);

		if ($this->else && !$this->else->isVoid()) {
			$e = Stream::legalStart($this->else->asBlock()->toString());
			$o .= $space . 'else' . $space . ($e === ';' ? '{}' : $e);
		}

		return $o;
	}

	public function last() {
		return $this;
	}

	public function condition() {
		return $this->condition;
	}

	public function then() {
		return $this->then;
	}

	public function _else() {
		return $this->else;
	}

	public function countLetters(&$letters) {
		$letters['i'] += 1;
		$letters['f'] += 1;

		$this->condition->countLetters($letters);
		$this->then->countLetters($letters);
		if ($this->else) {
			$letters['e'] += 2;
			$letters['l'] += 1;
			$letters['s'] += 1;

			$this->else->countLetters($letters);
		}
	}

	public function moveExpression(Expression $x) {
		$this->condition = new CommaExpression(array_merge($x->nodes(), $this->condition->nodes()));
		return true;
	}

	public function breaking() {
		// bail early if we don't have to break
		if (!$this->else) {
			return null;
		}

		return null;

		// check if we have a breaking statement here. In that case, discard all unreachable
		// nodes and return the resulting statements

		if ($new = $this->then->asBlock()->breaking()) {
			// the then clause breaks, no need for the else

			$result = array(new IfNode($this->condition, new BlockStatement($new)));
			foreach ($this->else->asBlock()->nodes as $n) {
				$result[] = $n;
			}

			return $result;
		}
	}
}
