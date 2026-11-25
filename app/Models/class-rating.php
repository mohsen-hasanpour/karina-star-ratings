<?php
/**
 * Immutable value object that represents the aggregated rating of a post.
 *
 * Internally every vote is normalized to a 0-5 scale so that changing the number of
 * stars never corrupts existing data. Values are converted to the configured scale
 * when read.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rating
 */
final class Rating {

	/**
	 * Internal normalization scale.
	 */
	const SCALE = 5;

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Number of votes.
	 *
	 * @var int
	 */
	private $count;

	/**
	 * Sum of all votes on the normalized scale.
	 *
	 * @var float
	 */
	private $sum;

	/**
	 * Configured maximum rating.
	 *
	 * @var int
	 */
	private $best;

	/**
	 * Constructor.
	 *
	 * @param int   $post_id Post ID.
	 * @param int   $count   Number of votes.
	 * @param float $sum     Sum of votes on the 0-5 scale.
	 * @param int   $best    Configured maximum rating.
	 */
	public function __construct( $post_id, $count, $sum, $best ) {
		$this->post_id = (int) $post_id;
		$this->count   = max( 0, (int) $count );
		$this->sum     = max( 0.0, (float) $sum );
		$this->best    = max( 1, (int) $best );
	}

	/**
	 * Post ID.
	 *
	 * @return int
	 */
	public function post_id() {
		return $this->post_id;
	}

	/**
	 * Number of votes.
	 *
	 * @return int
	 */
	public function count() {
		return $this->count;
	}

	/**
	 * Maximum rating.
	 *
	 * @return int
	 */
	public function best() {
		return $this->best;
	}

	/**
	 * Whether the post has at least one vote.
	 *
	 * @return bool
	 */
	public function has_votes() {
		return $this->count > 0;
	}

	/**
	 * Average on the configured scale (unrounded).
	 *
	 * @return float
	 */
	public function average() {
		if ( ! $this->count ) {
			return 0.0;
		}

		return min( (float) $this->best, ( $this->sum / $this->count ) * $this->best / self::SCALE );
	}

	/**
	 * Average rounded for display.
	 *
	 * @param int $decimals Number of decimals.
	 * @return float
	 */
	public function rounded( $decimals = 1 ) {
		return round( $this->average(), max( 0, (int) $decimals ) );
	}

	/**
	 * Average as a percentage of the maximum rating.
	 *
	 * @return float
	 */
	public function percent() {
		return round( $this->average() / $this->best * 100, 2 );
	}

	/**
	 * Fill percentage (0-100) for the star at a 1-based position.
	 *
	 * @param int $index Star position.
	 * @return float
	 */
	public function fill( $index ) {
		$fill = ( $this->average() - ( (int) $index - 1 ) ) * 100;

		return round( max( 0.0, min( 100.0, $fill ) ), 1 );
	}

	/**
	 * Array representation (used by the REST API and template helpers).
	 *
	 * @param int $decimals Number of decimals for the average.
	 * @return array<string, mixed>
	 */
	public function to_array( $decimals = 1 ) {
		$fills = array();
		for ( $i = 1; $i <= $this->best; $i++ ) {
			$fills[] = $this->fill( $i );
		}

		return array(
			'post_id' => $this->post_id,
			'average' => $this->rounded( $decimals ),
			'count'   => $this->count,
			'best'    => $this->best,
			'percent' => $this->percent(),
			'fills'   => $fills,
		);
	}
}
