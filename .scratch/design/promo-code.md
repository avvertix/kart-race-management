# Promo Code or Discount Code


## Current implementation

Currently the championship organizer store promotions as bonus entries. Each driver get a bonus entry with the amount of bonuses to use during a championship. This is done at the start of the championship depending on external conditions.



app\Actions\CreateBonus.php
app\Models\Bonus.php
app\Models\BonusMode.php
app\Models\BonusType.php
app\Models\Participant.php
app\Listeners\ApplyBonusToParticipant.php
app\Actions\CalculateParticipationCost.php


## Pitfalls

- Licence number or fiscal code is required
  - licence number might be unknown as we never saw the participants before
  - licence number is outdated as every year they can change
  - foreign participants do not have a fiscal code
  - licence number might be wrong because of human error
- It is easy to forget that a participant had a bonus


## Brief of the Promo/Discount code feature


- Generated on-demand with a specific code
- Target a championship, future or current depends on the discount nature, could be a prize/award compensation or just a discount
- Discounts can be like bonus, participation fee for X times or as a balance -> a promo_code/discount_code entitles a certain reduction of participation fee
- Can be redeemed by a user, and so is claimed by that user, that will use it when submitting the participation form of a race. Once reclaimed no other user can reclaim the same code
- has a link with a QR code to see/copy the code and check how many times it was used
- can be saved in the user phone wallet (spatie/laravel-mobile-pass package)



