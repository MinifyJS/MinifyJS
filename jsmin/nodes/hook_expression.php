<?php
class HookExpression extends Expression {
	public function __construct(Expression $hook, Expression $then, Expression $else) {
		$this->left = $hook;
		$this->middle = $then;
		$this->right = $else;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);
		$this->middle = $this->middle->visit($ast);
		$this->right = $this->right->visit($ast);

		$condition = $this->left->asBoolean();

		if ($condition === true) {
			return $this->middle;
		} elseif ($condition === false) {
			return $this->right;
		}

		if ($this->middle instanceof AssignExpression && $this->right instanceof AssignExpression
				&& $this->middle->assignType() === $this->right->assignType()
				&& $this->middle->left()->toString() === $this->right->left()->toString()) {
			$result = new AssignExpression(
				$this->middle->assignType(),
				$this->middle->left(),
				new HookExpression(
					$this->left,
					$this->middle->right(),
					$this->right()->right()
				)
			);

			return $result->visit($ast);
		}

		if ($this->middle instanceof IndexExpression && $this->right instanceof IndexExpression
				&& $this->middle->left()->toString() === $this->right->left()->toString()) {
			$result = new IndexExpression(
				$this->middle->left(),
				new HookExpression($this->left, $this->middle->right(), $this->right->right())
			);

			return $result->visit($ast);
		}

		return AST::bestOption(array(
			$this,
			new HookExpression(
				$this->left->negate(),
				$this->right,
				$this->middle
			)
		));

	}

	public function gone() {
		$this->left->gone();
		$this->middle->gone();
		$this->right->gone();
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->middle->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		$space = AST::$options['beautify'] ? ' ' : '';

		return $this->group($this, $this->left, false)
			. $space . '?' . $space . $this->group($this, $this->middle)
			. $space . ':' . $space . $this->group($this, $this->right);
	}

	public function negate() {
		return AST::bestOption(array(
			parent::negate(),
			new HookExpression($this->left, $this->middle->negate(), $this->right->negate())
		));
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 2;
	}
}
