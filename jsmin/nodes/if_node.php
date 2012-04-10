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

		$result = null;

		if ($this->then instanceof Expression && $this->else instanceof Expression) {
			$result = new HookExpression($this->condition, $this->then, $this->else);
			$option = new HookExpression($this->condition->negate(), $this->else, $this->then);

			$result = strlen($result->toString()) <= strlen($option->toString()) ? $result : $option;
		} elseif (!$this->else && $this->then instanceof Expression) {
			$and = new AndExpression($this->condition, $this->then);
			$or = new OrExpression($this->condition->negate(), $this->then);

			$result = (strlen($and->toString()) <= strlen($or->toString())) ? $and : $or;
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
		$useBlock = null;
		if ($this->else) {
			if (($last = $this->then->last()) && $last instanceof IfNode && !$last->else) {
				$useBlock = false;
			}
		}

		$o = 'if(' . $this->condition->toString() . ')' . $this->then->asBlock()->toString($useBlock, true);
		if ($this->else) {
			$e = Stream::legalStart($this->else->asBlock()->toString());
			$o .= 'else' . ($e === ';' ? '{}' : $e);
		}

		return $o;
	}

	public function hasStructure(Node $n) {
		return $n instanceof IfNode
			&& (!$n->then === !$this->then)
			&& (!$n->else === !$this->else);
	}
}