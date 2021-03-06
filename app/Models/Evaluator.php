<?php
/**
 * Project: ASPES.msc
 * Author:  Chukwuemeka Nwobodo (jcnwobodo@gmail.com)
 * Date:    9/11/2016
 * Time:    9:45 PM
 **/

namespace App\Models;

use App\Models\DataTypes\FuzzyNumber;
use App\Models\Helpers\FuzzyAHP;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Evaluator
 *
 * @package App\Models
 */
class Evaluator extends Model
{
    use SoftDeletes;
    use FuzzyAHP;

    const EVALUATOR      = 1;
    const DECISION_MAKER = 2;

    const CR_CAP = 0.1;

    /**
     * @var array
     */
    protected $dates    = ['deleted_at'];
    protected $casts    = ['comparison_matrix' => 'array', 'consistency_ratio' => 'float'];
    protected $hidden   = ['comparison_matrix'];
    protected $fillable = ['exercise_id', 'user_id', 'type'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comparisons()
    {
        return $this->hasMany(Comparison::class);
    }

    /**
     * @param bool $reCalc
     *
     * @return array|mixed
     */
    public function getComparisonMatrix($reCalc = false)
    {
        if ($this->exercise->isPublished()) {
            $arr = $this->comparison_matrix;
            if (!is_array($arr) or $reCalc) {
                $arr = $this->comparison_matrix = $this->buildComparisonMatrix();
                $this->save();
            }

            return $arr;
        }

        $arr = $this->comparison_matrix = $this->buildComparisonMatrix();
        $this->save();

        return $arr;
    }

    /**
     * @param bool $reCalc
     *
     * @return float|mixed
     */
    public function getConsistencyRatio($reCalc = false)
    {
        if ($this->exercise->isPublished()) {
            $CR = $this->consistency_ratio;
            if (!is_float($CR) or $reCalc) {
                $CR = $this->consistency_ratio = $this->calcConsistencyRatio();
                $this->save();
            }

            return $CR;
        }

        $CR = $this->consistency_ratio = $this->calcConsistencyRatio();
        $this->save();

        return $CR;
    }

    /**
     * @param bool $reCalc
     *
     * @return bool
     */
    public function hasAcceptableCR($reCalc = false)
    {
        $CR = $this->getConsistencyRatio($reCalc);

        return $CR <= self::CR_CAP;
    }

    /**
     *
     */
    public function clearComparisons()
    {
        $this->comparisons()->forceDelete();
        $this->comparison_matrix = null;
        $this->consistency_ratio = null;
        $this->save();
    }

    /**
     * @return array
     */
    protected function buildComparisonMatrix()
    {
        $matrix = [];
        /**
         * @var Collection $comparisons
         */
        $comparisons = $this->comparisons;

        /**
         * @var Comparison $comparison
         */
        foreach ($comparisons as $comparison) {
            $FN = new FuzzyNumber($comparison->FCV->value);
            $matrix[ $comparison->factor1->id ][ $comparison->factor2->id ] = $FN;
            $matrix[ $comparison->factor2->id ][ $comparison->factor1->id ] = $FN->reciprocal();
        }
        foreach ($this->exercise->factors as $factor) {
            $matrix[ $factor->id ][ $factor->id ] = new FuzzyNumber([1, 1, 1]);
        }

        return $matrix;
    }

    /**
     * @return float
     * @throws \Exception
     */
    protected function calcConsistencyRatio()
    {
        $compMatrix = $this->getComparisonMatrix(true);
        $MATRIX = $this->defuzzifyComparisonMatrix($compMatrix);

        $CI_ComparisonMatrix = $this->calcConsistencyIndex($MATRIX);
        $CI_RandomMatrix = $this->randomConsistencyIndex(count($MATRIX));

        return $CI_ComparisonMatrix / $CI_RandomMatrix;
    }
}
