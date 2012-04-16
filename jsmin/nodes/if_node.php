<?php
class IfNode extends Node {
	protected $condition;
	protected $then;

	protected $else;

	public function __construct(Expression $cond, Node $then, Node $else = null) {
		$this->condition = $cond;
		$this->then = $then->asBlock();
		$this->else = $else;

		$this->condition->parent($this);
		$this->then->parent($this);
		if ($this->else) {
			$this->else = $this->else->asBlock();
			$this->else->parent($this);
		}

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->condition = $this->condition->visit($ast);

		$this->then = $this->then->visit($ast);

		if ($this->else) {
			$this->else = $this->else->visit($ast);
		}

		if ($this->else && $this->then->isVoid()) {
			$this->condition = $this->condition->negate()->visit($ast);
			$this->then = $this->else;
			$this->else = null;
		}

		while ($this->then instanceof AndExpression) {
			$this->condition = new AndExpression($this->condition, $this->then->left());
			$this->then = $this->then->right();
		}

		if ($this->then->isVoid() && (!$this->else || $this->else->isVoid())) {
			return $this->condition;
		}

		$result = null;

		if ($this->then instanceof Expression && $this->else instanceof Expression) {
			$result = AST::bestOption(array(
				new HookExpression($this->condition->negate(), $this->else, $this->then),
				new HookExpression($this->condition, $this->then, $this->else)
			));
		} elseif (!$this->else && $this->then instanceof Expression) {
			$result = AST::bestOption(array(
				new AndExpression($this->condition, $this->then),
				new OrExpression($this->condition->negate(), $this->then)
			));
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
		} elseif ($this->then && $this->else) {
			$option = new IfNode($this->condition->negate(), $this->else, $this->then);

			if (strlen($this->toString()) > strlen($option->toString())) {
				$result = $option;
			}
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

	public function toString() {
		$noBlock = null;

		if ($this->else) {
			$noBlock = false;

			// does not work
			//$last = $this->then->last();
			//if (($last = $this->then->last()) instanceof IfNode && !$last->else) {
			//	$noBlock = false;
			//}
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
