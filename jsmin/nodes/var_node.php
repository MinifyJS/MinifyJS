<?php
class VarNode extends Node {
	protected $name;
	protected $initializer;

	public function __construct(IdentifierExpression $i, Expression $init = null) {
		$this->name = $i;
		$this->initializer = $init;

		$this->write();

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->name = $this->name->visit($ast);

		if ($this->initializer) {
			$this->initializer = $this->initializer->visit($ast);
		}

		return $this;
	}

	public function write() {
		return $this->name->write();
	}

	public function name() {
		return $this->name;
	}

	public function collectStatistics(AST $ast, $write = false) {
		$this->name->collectStatistics($ast, $write);

		if ($this->initializer) {
			if ($this->initializer instanceof ConstantExpression && $this->initializer->mayInline()) {
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