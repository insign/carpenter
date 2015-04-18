<?php namespace Michaeljennings\Carpenter\Components;

use Michaeljennings\Carpenter\Contracts\Cell as CellContract;
use Michaeljennings\Carpenter\Contracts\Column;

class Cell implements CellContract {

    /**
     * The cell value
     *
     * @var string
     */
    public $value;

    /**
     * The row the cell belongs to.
     *
     * @var string
     */
    protected $row;

    /**
     * The key from the key value pair.
     *
     * @var string|bool
     */
    protected $key;

    /**
     * The column the cell is in.
     *
     * @var Column
     */
    protected $column;

    public function __construct($value, $row, Column $column)
    {
        $this->row = $row;
        $this->column = $column;

        $this->createCell($value, $row, $column);
    }

    /**
     * Run the column presenter on the cell value.
     *
     * @param  string   $value
     * @param  mixed  	$row
     * @param  Column   $column
     */
    protected function createCell($value, $row, $column)
    {
        if ($column->hasPresenter()) {
            $callback = $column->getPresenter();
            $value = $callback($value, $row);
        }

        $this->value = $value;
    }

    /**
     * Check if this cell is a spreadsheet cell and then render it as necessary.
     *
     * @return string
     */
    public function renderSpreadsheetCell()
    {
        if ($this->column) {
            if ($this->column->hasSpreadsheetCell()) {
                $cell = new SpreadsheetCell($this->value, $this->row->id);
                $callback = $this->column->getSpreadsheetCell();
                $callback($cell);

                return $cell->render();
            } else {
                return $this->value;
            }
        }
    }
}