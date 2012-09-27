<?php
class HookExpression extends Expression {
	public function __construct(Expression $hook, Expression $then, Expression $else) {
		$this->left = $hook;
		$this->middle = $then;
		$this->right = $else;

		parent::__construct();
	}


	public function visit2(AST $ast, Node $parent = null) {
		$that = new HookExpression(
			$this->left->visit($ast, $this),
			$this->middle->visit($ast, $this),
			$this->right->visit($ast, $this)
		);

		$condition = $that->left->asBoolean();

		if ($condition === true) {
			$that->left->gone();
			$that->right->gone();

			return $that->middle;
		} elseif ($condition === false) {
			$that->left->gone();
			$that->middle->gone();

			return $that->right;
		}

		AST::$options['squeeze'] = false;
		if ($that->middle instanceof AssignExpression && $that->right instanceof AssignExpression
				&& $that->middle->assignType() === $that->right->assignType()
				&& $that->middle->left()->toString() === $that->right->left()->toString()) {
			$result = new AssignExpression(
				$that->middle->assignType(),
				$that->middle->left(),
				new HookExpression(
					$that->left,
					$that->middle->right(),
					$that->right()->right()
				)
			);

			AST::$options['squeeze'] = true;

			return $result->visit($ast, $parent);
		}

		if ($that->middle instanceof IndexExpression && $that->right instanceof IndexExpression
				&& $that->middle->left()->toString() === $that->right->left()->toString()) {
			$result = new IndexExpression(
				$that->middle->left(),
				new HookExpression($that->left, $that->middle->right(), $that->right->right())
			);

			AST::$options['squeeze'] = true;

			return $result->visit($ast, $parent);
		}

		AST::$options['squeeze'] = true;

		if ($that->type() === 'boolean' && (null !== $left = $that->middle->asBoolean()) && (null !== $right = $that->right->asBoolean())) {
			$result = null;
			if ($right === true && $left === false) {
				$result = $that->left->negate()->boolean();
			} elseif ($left === true && $right === false) {
				$result = $that->left->boolean();
			}

			if ($result) {
				$that->middle->gone();
				$that->right->gone();
				return $result;
			}
		}

		return AST::bestOption(array(
			$that,
			new HookExpression(
				$this->left->negate(),
				$this->right,
				$this->middle
			)
		))->resolveLeftSequence();

	}

	public function resolveLeftSequence(Expression $that = null) {
		if ($this->left instanceof CommaExpression) {
			$x = $this->left->nodes();

			return AST::bestOption(array(
				new CommaExpression(array(
					new CommaExpression(array_slice($x, 0, -1)),
					new HookExpression(
						end($x),
						$this->middle,
						$this->right
					)
				)),
				$this
			));
		}

		return $this;
	}


	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);
		$this->middle = $this->middle->visit($ast, $this);
		$this->right = $this->right->visit($ast, $this);

		$condition = $this->left->asBoolean();

		if ($condition === true) {
			$this->left->gone();
			$this->right->gone();

			return $this->middle;
		} elseif ($condition === false) {
			$this->left->gone();
			$this->middle->gone();

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

			return $result->visit($ast, $parent);
		}

		if ($this->middle instanceof IndexExpression && $this->right instanceof IndexExpression
				&& $this->middle->left()->toString() === $this->right->left()->toString()) {
			$result = new IndexExpression(
				$this->middle->left(),
				new HookExpression($this->left, $this->middle->right(), $this->right->right())
			);

			return $result->visit($ast, $parent);
		}

		if ($this->type() === 'boolean' && (null !== $left = $this->middle->asBoolean()) && (null !== $right = $this->right->asBoolean())) {
			$result = null;
			if ($right === true && $left === false) {
				$result = $this->left->negate()->boolean();
			} elseif ($left === true && $right === false) {
				$result = $this->left->boolean();
			}

			if ($result) {
				$this->middle->gone();
				$this->right->gone();
				return $result;
			}
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
		if (($this->right->type() === ($left = $this->middle->type())) && $left !== null) {
			return $left;
		}

		return null;
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
		$this->middle->countLetters($letters);
		$this->right->countLetters($letters);
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects() || $this->middle->hasSideEffects() || $this->right->hasSideEffects();
	}

	public function precedence() {
		return 2;
	}
}
