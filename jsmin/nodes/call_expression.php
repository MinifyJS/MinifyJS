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

		if (!$this->right && $this->left instanceof DotExpression) {
			if ($this->left->right()->name() === 'toString' && !$argc) {
				$result = AST::bestOption(array(
					new PlusExpression(new String('', false), $this->left->left()),
					new PlusExpression($this->left->left(), new String('', false))
				));
			}
		}

		if ($this->left instanceof DotExpression && $this->left->right()->name() === 'match'
				&& $this->left->left()->type() === 'string' && $argc === 1
				&& $this->right[0]->actualType() === 'regexp' && !$this->right[0]->hasFlag('g')) {
			$result = new CallExpression(
				new DotExpression($this->right[0], new Identifier(null, 'exec')),
				array($this->left->left())
			);

			return $result->visit($ast);
		}

		if (AST::$options['strip-debug'] && $this->left instanceof DotExpression && $this->left->left()->value() === 'console') {
			if (!$this->left->left()->isLocal()) {
				$nodes = $this->right;
				$nodes[] = new VoidExpression(new Number(0));

				$result = new CommaExpression($nodes);
				return $result->visit($ast);
			}
		}

		if ($this->left instanceof IdentifierExpression && !$this->left->isLocal()) {
			switch ($this->left->value()) {
			case 'RegExp':
				$flags = null;
				$regexp = null;

				if ($argc && $this->right[0]->actualType() === 'regexp') {
					if ($argc === 1 || ($argc === 2 && $this->right[1]->isVoid())) {
						// it was already visited
						return $this->right[0];
					}
				}

				switch ($argc) {
				case 2:
					if (null === $flags = $this->right[1]->asString()) {
						break;
					}
				case 1:
					if (null === $regexp = $this->right[0]->asString()) {
						break;
					}

					$regexp = '/' . str_replace('/', '\/', $regexp) . '/' . $flags;
					break;
				}

				if ($regexp !== null) {
					try {
						$result = new RegExp($regexp);
					} catch(Exception $e) {}
				}
				break;
			case 'Array':
				if ($argc !== 1) {
					$result = new ArrayExpression($this->right);
				} elseif ($this->right[0]->asNumber() == 0) {
					$result = new ArrayExpression(array());
				}

				break;
			case 'Boolean':
				if ($argc === 0) {
					return new Boolean(false);
				} elseif ($argc === 1) {
					$result = $this->right[0]->boolean();
				} else {
					$result = clone $this;
					$result->right[0] = $result->right[0]->looseBoolean();

					return $result;
				}

				break;
			case 'String':
				if ($argc === 0) {
					$result = new String('', false);
				} elseif ($argc === 1) {
					$result = AST::bestOption(array(
						new PlusExpression(new String('', false), $this->right[0]),
						new PlusExpression($this->right[0], new String('', false))
					));
				}

				break;
			case 'Object':
				if (!$this->right) {
					return new ObjectExpression(array());
				}
				break;
			case 'isNaN':
				if ($argc === 1 && ($this->right[0] instanceof IdentifierExpression || $this->right[0]->isConstant())) {
					return AST::bestOption(array(
						$this,
						new NotEqualExpression($this->right[0], $this->right[0], ComparisonExpression::NOT_STRICT)
					));
				}
			}
		}

		if (!$result && $this->left instanceof DotExpression) {
			// check for array shortening..
			if ($this->left->left()->actualType() === 'array' && $this->left->right()->name() === 'join') {
				if (!$this->right || ($argc === 1 && $this->right[0]->asString() === ',')) {
					$result = new PlusExpression($this->left->left(), new String('', false));
				}
			} elseif ($this->left->left()->actualType() === 'date' && $this->left->right()->name() === 'getTime') {
				if (!$this->right) {
					$result = new UnaryPlusExpression($this->left->left());
				}
			}
		}

		if ($ast->hasStats() && !$result && $this->left instanceof FunctionExpression) {
			if (!$this->right && $n = $this->left->onlyReturns()) {
				$result = $n;
			} else {
				$this->left->optimizeArguments();
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
		$o = array();
		foreach($this->right as $x) {
			$n = $x->toString();
			if ($x instanceof CommaExpression) {
				$n = '(' . $n . ')';
			}

			$o[] = $n;
		}

		// small exception here: when left is a NewExpression with args, no need for grouping
		return ($this->left instanceof NewExpression && $this->left->right() ? $this->left->toString() : $this->group($this, $this->left))
			. '(' . implode(',' . (AST::$options['beautify'] ? ' ' : ''), $o) . ')';
	}

	public function precedence() {
		return 17;
	}

	public function isRedundant() {
		return false;
	}

	public function gone() {
		$this->left->gone();
		foreach($this->right as $n) {
			$n->gone();
		}
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);

		foreach ($this->right as $n) {
			$n->countLetters($letters);
		}
	}

	public function isConstant() {
		return false;
	}
}
