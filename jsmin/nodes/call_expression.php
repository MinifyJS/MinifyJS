<?php
class CallExpression extends Expression {
	public function __construct(Expression $what, array $args) {
		$this->left = $what;
		$this->right = $args;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		foreach($this->right as $i => $r) {
			$this->right[$i] = $r->visit($ast);
		}

		$argc = count($this->right);

		$result = null;

		if (!$this->right && $this->left instanceof DotExpression && $this->left->right() instanceof Identifier) {
			if ($this->left->right()->name() === 'toString' && !$argc) {
				$result = new PlusExpression($this->left->left(), new String('', false));
			}
		}

		if ($this->left->value() === 'RegExp') {
			$flags = null;
			$regexp = null;

			switch ($argc) {
			case 2:
				if (null === $flags = $this->right[1]->asString()) {
					break;
				}
			case 1:
				if (null === $regexp = $this->right[0]->asString()) {
					break;
				}

				$regexp = '/' . str_replace(array('\\', '/'), array('\\\\', '\/'), $this->right[0]->asString()) . '/' . $flags;
				break;
			}

			if ($regexp !== null) {
				$result = new RegExp($regexp);
			}
		}

		return $result ? $result->visit($ast) : $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		foreach($this->right as $r) {
			$r->collectStatistics($ast);
		}
	}

	public function toString() {
		return $this->group($this, $this->left) . '(' . implode(',', $this->right) . ')';
	}

	public function precedence() {
		return 17;
	}

	public function isRedundant() {
		return false;
	}
}
