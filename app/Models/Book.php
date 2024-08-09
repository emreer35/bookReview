<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory;
    protected $table = 'books';
    protected $guarded = [];
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeTitle(Builder $builder, string $title): Builder
    {
        return $builder->where('title', 'LIKE', '%' . $title . '%');
    }

    // buraya scope popularin icerisinde ki degeri yazmamizin sebebi reviews kismini 0 olarak vermesi aynisini highest rated da da ypioyurz
    public function scopeWithReviewsCount(Builder $builder, $from = null, $to = null): Builder
    {
        return $builder->withCount(['reviews' => fn(Builder $query) => $this->dateRangeFilter($query, $from, $to)]);
    }
    public function scopeWithAvgRating(Builder $builder, $from = null, $to = null): Builder {
        return $builder->withAvg(['reviews'=> fn(Builder $query)=> $this->dateRangeFilter($query,$from,$to)],'rating');
    }

    public function scopePopular(Builder $builder, $from = null, $to = null): Builder
    {
        // $this kullanmiyoruz cunku temel olarak sifiralyacaktir 
        // oyzuden $builder kullaniyoiruz past query object
        // arrow fucntion 
        // arrow functionlar burada sadece tek bir deger aliyor iki deger alamaz 
        // return $builder->withCount(['reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)])
        return $builder->withReviewsCount()
            ->orderBy('reviews_count', 'desc');
    }
    public function scopeHighestRated(Builder $builder, $from = null, $to = null): Builder
    {
        // burada da ayni sekilde 
        // return $builder->withAvg(['reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)], 'rating')
        return $builder->withAvgRating()
            ->orderBy('reviews_avg_rating', 'desc');
    }
    public function scopeMinReviews(Builder $builder, int $minReviews): Builder
    {
        return $builder->having('reviews_count', '>=', $minReviews);
    }



    private function dateRangeFilter(Builder $builder, $from = null, $to = null)
    {
        if ($from && !$to) {
            $builder->where('created_at', '>=', $from);
        } elseif (!$from && $to) {
            $builder->where('created_at', '<=', $to);
        } elseif ($from && $to) {
            $builder->whereBetween('created_at', [$from, $to]);
        }
    }

    public function scopePopularLastMonth(Builder $builder): Builder
    {
        return $builder->Popular(now()->subMonth(), now())
            ->HighestRated(now()->subMonth(), now())
            ->MinReviews(2);
    }
    public function scopePopularLast6Months(Builder $builder): Builder
    {
        return $builder->Popular(now()->subMonths(6), now())->HighestRated(now()->subMonths(6), now())
            ->MinReviews(5);
    }
    public function scopeHighestRatedLastMonth(Builder $builder): Builder
    {
        return $builder->HighestRated(now()->subMonth(), now())->Popular(now()->subMonth(), now())
            ->MinReviews(2);
    }
    public function scopeHighestRatedLast6Months(Builder $builder): Builder
    {
        return $builder->HighestRated(now()->subMonths(6), now())->Popular(now()->subMonths(6), now())
            ->MinReviews(5);
    }
    protected static function booted()
    {
        Static::updated(fn(Book $book)=>cache()->forget('books:' . $book->id));
        Static::deleted(fn(Book $book)=>cache()->forget('books:' . $book->id));
    }
}
