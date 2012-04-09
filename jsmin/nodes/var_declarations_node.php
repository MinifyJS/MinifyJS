<?php
class VarDeclarationsNode extends Node {
	public function __construct(array $decs) {
		$this->nodes = $decs;

		parent::__construct();
	}

	public function visit(AST $ast) {
		foreach($this->nodes as $i => $e) {
			$this->nodes[$i] = $e->visit($ast);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $d) {
			$d->collectStatistics($ast);
		}
	}

	public function toString() {
		$o = array();
		foreach($this->nodes as $d) {
			$a = $d->toString();
			if ($a !== '') {
				$o[] = substr($a, 4);
			}
		}

		return $o ? 'var ' . join(',', $o) : '';
	}
}
