<?php
class VarNode extends Node {
	protected $name;
	protected $initializer;

	public function __construct(IdentifierExpression $i, Expression $init = null) {
		$this->name = $i;
		$this->initializer = $init ?: new VoidExpression(new Number(0));

		$this->write();

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->name = $this->name->visit($ast);
		$this->initializer = $this->initializer->visit($ast);

		if ($ast->hasStats() && !$this->name->keep(1)) {
			$this->name->gone();

			if ($this->initializer) {
				return $this->initializer;
			}

			return new VoidExpression(new Number(0));
		}

		return $this;
	}

	public function write() {
		return $this->name->write();
	}

	public function name() {
		return $this->name;
	}

	public function gone() {
		$this->name->gone();
		if ($this->initializer) {
			$this->initializer->gone();
		}
	}

	public function collectStatistics(AST $ast, $write = false) {
		$this->name->collectStatistics($ast, $write);

		if ($this->initializer) {
			if ($this->name->get()->scope()->parent() && $this->initializer->mayInline()) {
				$this->name->initializer($this->initializer);
			}

			$this->initializer->collectStatistics($ast);
		}
	}

	public function toString() {
		$init = $this->initializer;

		if (!$this->name->keep(1) && (!$init || $init->isRedundant())) {
			return '';
		}

		$space = AST::$options['beautify'] ? ' ' : '';

		return 'var ' . $this->name . ($init && !$init->isVoid() ? ($space . '=' . $space . $init->toString()) : '');
	}
}