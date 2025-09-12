<?php

namespace App\Services;

use App\Models\Rental;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RentalContractService
{
    public function generateContract(Rental $rental)
    {
        $rental->load([
            'vehicle.owner',
            'vehicle.images',
            'renter',
            'payment'
        ]);
        
        $data = [
            'rental' => $rental,
            'vehicle' => $rental->vehicle,
            'owner' => $rental->vehicle->owner,
            'renter' => $rental->renter,
            'payment' => $rental->payment,
            'contractNumber' => $this->generateContractNumber($rental),
            'generatedAt' => Carbon::now(),
            'startDate' => Carbon::parse($rental->start_date),
            'endDate' => Carbon::parse($rental->end_date),
            'duration' => Carbon::parse($rental->start_date)->diffInDays($rental->end_date) + 1,
            'termsAndConditions' => $this->getTermsAndConditions(),
        ];
        
        $pdf = PDF::loadView('pdf.rental-contract', $data);
        
        return $pdf;
    }
    
    private function generateContractNumber(Rental $rental): string
    {
        return sprintf(
            'CLR-%s-%05d',
            Carbon::parse($rental->created_at)->format('Ymd'),
            $rental->id
        );
    }
    
    private function getTermsAndConditions(): array
    {
        return [
            'general' => [
                'title' => 'Conditions Générales',
                'items' => [
                    'Le locataire s\'engage à utiliser le véhicule en bon père de famille.',
                    'Le véhicule doit être rendu dans le même état qu\'au moment de la prise en charge.',
                    'Le locataire est responsable de tous les dommages causés pendant la période de location.',
                    'Le carburant est à la charge du locataire. Le véhicule doit être rendu avec le même niveau de carburant.',
                    'Les contraventions et amendes sont à la charge du locataire.',
                ]
            ],
            'insurance' => [
                'title' => 'Assurance',
                'items' => [
                    'Le locataire doit être titulaire d\'un permis de conduire valide.',
                    'Une franchise de 500€ est applicable en cas de sinistre.',
                    'Le vol et les dégâts causés par négligence ne sont pas couverts.',
                ]
            ],
            'cancellation' => [
                'title' => 'Annulation',
                'items' => [
                    'Annulation gratuite jusqu\'à 48h avant le début de la location.',
                    'Annulation entre 48h et 24h : 50% du montant total retenu.',
                    'Annulation moins de 24h avant : 100% du montant total retenu.',
                ]
            ],
            'payment' => [
                'title' => 'Paiement',
                'items' => [
                    'Le paiement intégral est dû au moment de la réservation.',
                    'Une caution peut être demandée et sera restituée après vérification du véhicule.',
                ]
            ],
        ];
    }
    
    public function getContractFilename(Rental $rental): string
    {
        return sprintf(
            'contrat-location-%s-%s.pdf',
            $this->generateContractNumber($rental),
            Carbon::parse($rental->created_at)->format('Ymd')
        );
    }
}