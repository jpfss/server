<?php
/**
 * @package plugins.thumbnail
 * @subpackage model
 */

class wordWrapHelper
{
	protected $maxWidth;
	protected $maxHeight;
	protected $draw;
	protected $image;
	protected $fullText;
	protected $currentLineHeight = 0;
	protected $currentLineText = "";
	protected $lines = array();
	protected $currentLineLimit;
	protected $totalHeight = 0;

	const TEXT_DOES_NOT_FIT_ERR = "text doesn't fit the bounding box";

	function __construct($image, $draw, $fullText, $maxWidth, $maxHeight = null)
	{
		$this->image = $image;
		$this->draw = $draw;
		$this->fullText = $fullText;
		$this->image = $image;
		$this->maxWidth = $maxWidth;
		$this->maxHeight = $maxHeight;
		$this->currentLineLimit = $maxWidth;
		$this->currentLineText = "";
	}

	public function calculateWordWrap()
	{
		$foundSpace = false;
		$wordBeginPos = 0;
		$spaceMetrics = $this->image->queryFontMetrics($this->draw, " ");
		if($spaceMetrics['textWidth'] > $this->maxWidth)
		{
			throw new KalturaAPIException(KalturaThumbnailErrors::BAD_QUERY, self::TEXT_DOES_NOT_FIT_ERR);
		}

		$textLength = strlen($this->fullText);

		for($i = 0; $i < $textLength; $i++)
		{
			if($this->fullText[$i] == " ")
			{
				if(!$foundSpace)
				{
					$foundSpace = true;
					$text = substr($this->fullText, $wordBeginPos, $i-$wordBeginPos);
					$metrics = $this->image->queryFontMetrics($this->draw, $text);
					$this->tryAddTextToLine($text, $metrics);
				}

				$this->tryAddTextToLine(" ", $spaceMetrics);
			}
			else
			{
				if($foundSpace)
				{
					$foundSpace = false;
					$wordBeginPos = $i;
				}
			}
		}

		if(!$foundSpace)
		{
			$text = substr($this->fullText, $wordBeginPos, $i-$wordBeginPos);
			$metrics = $this->image->queryFontMetrics($this->draw, $text);
			$this->tryAddTextToLine($text, $metrics);
		}

		$this->commitCurrentLine();
		if($this->maxHeight && $this->totalHeight > $this->maxHeight)
		{
			throw new KalturaAPIException(KalturaThumbnailErrors::BAD_QUERY, self::TEXT_DOES_NOT_FIT_ERR);
		}

		return implode(PHP_EOL, $this->lines);
	}

	protected function tryAddTextToLine($text, $metrics)
	{
		$textWidth = $metrics['textWidth'];
		if($this->maxWidth)
		{
			if ($textWidth > $this->maxWidth)
			{
				throw new KalturaAPIException(KalturaThumbnailErrors::BAD_QUERY, self::TEXT_DOES_NOT_FIT_ERR);
			}

			if ($textWidth > $this->currentLineLimit)
			{
				$this->commitCurrentLine();
			}

			$this->currentLineLimit -= $textWidth;
		}

		$this->currentLineText .= $text;
		$this->currentLineHeight = max($metrics['textHeight'], $this->currentLineHeight);
	}

	protected function commitCurrentLine()
	{
		$this->lines[] = $this->currentLineText;
		$this->totalHeight += $this->currentLineHeight;
		$this->currentLineHeight = 0;
		$this->currentLineText = "";
		$this->currentLineLimit = $this->maxWidth;
	}
}