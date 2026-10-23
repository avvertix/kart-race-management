# Features to introduce or adapt - brief


## Driver and Competitor as objects

[Participant](app/Models/Participant.php) stores drivers, competitor, mechanic and vehicles in arrays with no type hints.

Moving to a typed object will improve validation and quality of data stored as well as prevent errors when using those data for UI or exports

The fields to look for:

- driver
- competitor
- mechanic
- vehicles

We can use `spatie/laravel-data` which is already installed an used to host configuration values in [Championship](app/Models/Championship.php), like WildcardSettingsData
PaymentSettingsData or LicenceSettingsData.

Data is stored and validated in [RegisterParticipant](app\Actions\RegisterParticipant.php) and [UpdateParticipantRegistration](app\Actions\UpdateParticipantRegistration.php)


## Handle companies with VAT as competitor

A [Participant](../../app/Models/Participant.php) competitor can be a company (aka a racing team) with VAT. Currently it is limited to individuals [RegisterParticipant](app\Actions\RegisterParticipant.php). We need to allow to select company and enter company name and VAT, no fiscal code, no date of birth.


## Support flow for licence emission or renewal

Organizers emit or renew licences, so far they process this request via email, but is time consuming and prone to errors as then we need to duplicate information to include as race number reservation [BibReservation.php](app\Models\BibReservation.php) and [Bonus](app\Models\Bonus.php). We need to do that because the organizer include licence in promotional packages that combine a discount to a race.

There are two provider of licences, ACI and ASI, this should be configurable somehow. Within ACI than there are sub-types of licences we need to be able to configure as well.

I envision the following process:

- User land on a licence page with the information
- There it is asked to create a profile (currently driver permissions)
- After that a multi-step form is presented (I'm waiting to know the exact steps of the current process), I'm expecting that the anagraphic part as asked in [participant form](resources\views\participant\partials\form.blade.php) will play a role
- User must upload attachments like identity card, medical certificate and others
- user can hold a draft of the request for as many day as required to compile all
- user submit the request and track the progress
- organizer can reject identity card or other documents and ask to recompile part of the forms if wrongly done
- Licence renewal/emission has a cost, there might be promotions reducing the price

So there are a few modules that we need to reference and a few modules we might re-create and then export directly in PDF, all uploaded material must be exported in a single PDF.

The licence emitting body will return a PDF with a temporary licence we must be able to send back to the person who requested the licence.

Once licence is emitted or renewed:

- organizer will upload the new temporary licence in PDF format for the user to download
- The licence number and anagraphical details are stored in the user profile
- The licence is valid for one year, usually the solar year in which the request was made, or processed (this last part is true in case the request arrived in december, usually those are processed in january to allow the full solar year validity)

A user can theoretically request more than 1 licence, for example competitor, mechanic and so on, these are separate requests

This is what they currently state in italian on their website as part of the procedure:

- Licenze concorrente conduttore minorenne carta d’identità e codice fiscale del minore e di entrambi i genitori-certificato medico agonistico specifico del karting in corso di validità – autorizzazione esercenti potestà genitoriale(vedi allegato)-delega(vedi allegato)-privacy firmata da entrambi i genitori(vedi allegato)
- Licenze concorrente conduttore maggiorenne carta d’identità e codice fiscale-certificato medico agonistico specifico del karting in corso di validità-delega(vedi allegato)-privacy (vedi allegato)
- Licenza assistente meccanico carta d’identità e codice fiscale-delega(vedi allegato)-privacy (vedi allegato)

**Other impacts**

- Currently we reference licence types as [DriverLicence](app\Models\DriverLicence.php), [CompetitorLicence](app\Models\CompetitorLicence.php). Local refer to one emitted by either ASI or ACI. I think this should probably be changed as we must be clear what it the licence provider for each championship as an evolution of [LicenceSettingsData](app\Data\LicenceSettingsData.php). The main challenge would be to keep the compatibility with the current flows as we will develop as other championships and races are still in progress on the live production platform as flow should be active before the end of the current season.
- explore possible correlation with [LinkedDrivers](app\Livewire\LinkedDrivers.php) and participant claimed_by field currently used to speed-up the compilation of the participant registration form. I'm expecting we will change how users with an account will compile the form, but we will see.

## Championship registration

A user can enroll in a championship. Enroll require a registered account on the platform. 

- User select championship
- User select the product, see the costs and pay
- User insert licence number and anagraphical data, if doesn't already have a stored licence as part of a renewal, otherwise selects it
- User can reserve a bib during the enrollment, if enabled or if previously renewed/asked for a licence with a promotion that entitled a bib reservation or other promotional codes
- if the license is not valid for the year of the championship
- the driver identified by the license is enrolled in the championship

Once enrolled the driver, via the user account can select to participate in races and all anagraphical data except category and vehicle or mechanic information are automatically compiled from the licence. Since a driver can register alone or under a competitor this probably should follow a similar approach of linked drivers as of today for the other infos. The bib is assigned ask asked during the licence renewal process or as selected during the championship enrollment

**Challenge**

- For the karting configuration (configured manually during deployment) the championship registration is required to reserve a race (bib) number and to link promotional codes or discounts
- For moto configuration (configured manually during deployment) the championship registration is required to reserve a race (bib) number and to get points for the championship awards, currently this is done calculating bonuses and the concept of wildcard [WildcardStrategy](app\Models\WildcardStrategy.php)
- The moto configuration require to pay an amount for the enrollment while karting championships may not require it


## Products and promo code

The organizer creates promotions that impacts

- licence renewal
- championship enroll

Usually these promotions comes in form of

- Discount of race fee based on awards of previous year championship results
- Discount of race fee based on licence renewal promotion

in addition the organizer can emit discount/promo codes affecting race participation costs, few ideas in .scratch\design\promo-code.md


## Select race number to reserve once the licence lands or is verified

Each user that request a licence or enroll in a championship can reserve the race number. Reservation works as today, first confirmed, first served.

To select the bib to reserve the user should be presented a screen that shows what's available and what's not with a free text input to specify what the user wants. Like a airplane or theater seat selection, although we need to take into account that we have numbers from 1 to 999 to select from.

Bib reservation must be confirmed, if driver enrollment is confirmed then bib reservation is confirmed for that driver with that licence number.
