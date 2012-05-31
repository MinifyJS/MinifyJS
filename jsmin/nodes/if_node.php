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
		$this->condition = $this->condition->visit($ast)->looseBoolean();
		$this->then = $this->then->visit($ast);

		if ($this->else) {
			$this->else = $this->else->visit($ast);
		}

		// if (*) {} else *; -> if (!*) *
		if ($this->else && $this->then->isVoid()) {
			$this->condition = $this->condition->negate()->visit($ast);
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
		$this->then = $this->then->optimizeBreak();
		if ($this->else) {
			$this->else = $this->else->optimizeBreak();

			if ($this->else->isVoid()) {
				$this->else = null;
			}
		}

		return $this;
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
		$noBlock = null;

		if (($this->else && !($this->then instanceof Expression)) || AST::$options['beautify']) {
			$noBlock = false;
		}

		$o = 'if(' . $this->condition->toString() . ')' . $this->then->asBlock()->toString($noBlock, true);

		if ($this->else) {
			$e = Stream::legalStart($this->else->asBlock()->toString());
			$o .= 'else' . ($e === ';' ? '{}' : $e);
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

	public function breaking() {
		// bail early if we don't have to break
		if (!$this->else) {
			return null;
		}

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
