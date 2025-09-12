<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de Location - {{ $contractNumber }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        
        .logo {
            font-size: 24pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        
        .contract-number {
            font-size: 14pt;
            color: #666;
            margin-top: 10px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #2563eb;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-col {
            display: table-cell;
            width: 50%;
            padding: 5px 10px;
            vertical-align: top;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 3px;
        }
        
        .info-value {
            color: #333;
        }
        
        .vehicle-details {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .rental-summary {
            background-color: #eff6ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .price-breakdown {
            margin-top: 10px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #d1d5db;
        }
        
        .price-label {
            color: #666;
        }
        
        .price-value {
            font-weight: bold;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14pt;
            font-weight: bold;
            border-top: 2px solid #2563eb;
            margin-top: 10px;
        }
        
        .terms-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .terms-category {
            margin-bottom: 20px;
        }
        
        .terms-title {
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        
        .terms-list {
            list-style-type: disc;
            margin-left: 20px;
        }
        
        .terms-item {
            margin-bottom: 5px;
            font-size: 10pt;
        }
        
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signature-grid {
            display: table;
            width: 100%;
        }
        
        .signature-col {
            display: table-cell;
            width: 45%;
            padding: 20px;
            text-align: center;
        }
        
        .signature-col:first-child {
            border-right: 1px solid #e5e7eb;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin: 40px 0 10px 0;
        }
        
        .signature-name {
            font-size: 10pt;
            color: #666;
        }
        
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .highlight {
            background-color: #fef3c7;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🚗 CarLocation</div>
        <h1>CONTRAT DE LOCATION DE VÉHICULE</h1>
        <div class="contract-number">N° {{ $contractNumber }}</div>
        <div>Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
    </div>
    
    <div class="section">
        <h2 class="section-title">1. PARTIES AU CONTRAT</h2>
        
        <div class="info-grid">
            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">PROPRIÉTAIRE</div>
                    <div class="info-value">
                        <strong>{{ $owner->name }}</strong><br>
                        Email: {{ $owner->email }}<br>
                        @if($owner->phone)
                            Téléphone: {{ $owner->phone }}<br>
                        @endif
                        @if($owner->address)
                            Adresse: {{ $owner->address }}
                        @endif
                    </div>
                </div>
                <div class="info-col">
                    <div class="info-label">LOCATAIRE</div>
                    <div class="info-value">
                        <strong>{{ $renter->name }}</strong><br>
                        Email: {{ $renter->email }}<br>
                        @if($renter->phone)
                            Téléphone: {{ $renter->phone }}<br>
                        @endif
                        @if($renter->driving_license_number)
                            Permis N°: {{ $renter->driving_license_number }}<br>
                            Valide jusqu'au: {{ \Carbon\Carbon::parse($renter->driving_license_expiry)->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2 class="section-title">2. VÉHICULE LOUÉ</h2>
        
        <div class="vehicle-details">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-col">
                        <div class="info-label">Marque & Modèle</div>
                        <div class="info-value">{{ $vehicle->brand }} {{ $vehicle->model }}</div>
                    </div>
                    <div class="info-col">
                        <div class="info-label">Année</div>
                        <div class="info-value">{{ $vehicle->year }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-col">
                        <div class="info-label">Immatriculation</div>
                        <div class="info-value">{{ $vehicle->registration_number ?? 'Non spécifié' }}</div>
                    </div>
                    <div class="info-col">
                        <div class="info-label">Type de véhicule</div>
                        <div class="info-value">{{ ucfirst($vehicle->vehicle_type ?? 'Non spécifié') }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-col">
                        <div class="info-label">Carburant</div>
                        <div class="info-value">{{ ucfirst($vehicle->fuel_type) }}</div>
                    </div>
                    <div class="info-col">
                        <div class="info-label">Transmission</div>
                        <div class="info-value">{{ $vehicle->transmission === 'manual' ? 'Manuelle' : 'Automatique' }}</div>
                    </div>
                </div>
                @if($vehicle->mileage)
                <div class="info-row">
                    <div class="info-col">
                        <div class="info-label">Kilométrage au départ</div>
                        <div class="info-value">{{ number_format($vehicle->mileage, 0, ',', ' ') }} km</div>
                    </div>
                    <div class="info-col">
                        <div class="info-label">Nombre de places</div>
                        <div class="info-value">{{ $vehicle->seats }} places</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2 class="section-title">3. PÉRIODE DE LOCATION</h2>
        
        <div class="rental-summary">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-col">
                        <div class="info-label">Date de début</div>
                        <div class="info-value">
                            <strong>{{ $startDate->format('d/m/Y') }}</strong>
                            à {{ $startDate->format('H:i') }}
                        </div>
                    </div>
                    <div class="info-col">
                        <div class="info-label">Date de fin</div>
                        <div class="info-value">
                            <strong>{{ $endDate->format('d/m/Y') }}</strong>
                            à {{ $endDate->format('H:i') }}
                        </div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-col">
                        <div class="info-label">Durée totale</div>
                        <div class="info-value">
                            <span class="highlight">{{ $duration }} jour{{ $duration > 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                    <div class="info-col">
                        <div class="info-label">Lieu de prise en charge</div>
                        <div class="info-value">{{ $vehicle->pickup_location ?? $vehicle->city }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2 class="section-title">4. CONDITIONS FINANCIÈRES</h2>
        
        <div class="price-breakdown">
            <div class="price-row">
                <span class="price-label">Prix de location ({{ $duration }} jour{{ $duration > 1 ? 's' : '' }})</span>
                <span class="price-value">{{ number_format($rental->total_price, 2, ',', ' ') }} €</span>
            </div>
            
            @if($payment)
                @if($payment->platform_fee > 0)
                <div class="price-row">
                    <span class="price-label">Frais de service</span>
                    <span class="price-value">{{ number_format($payment->platform_fee, 2, ',', ' ') }} €</span>
                </div>
                @endif
                
                @if($payment->payment_fee > 0)
                <div class="price-row">
                    <span class="price-label">Frais de transaction</span>
                    <span class="price-value">{{ number_format($payment->payment_fee, 2, ',', ' ') }} €</span>
                </div>
                @endif
                
                <div class="total-row">
                    <span>TOTAL À PAYER</span>
                    <span>{{ number_format($payment->total_amount, 2, ',', ' ') }} €</span>
                </div>
                
                <div style="margin-top: 10px; font-size: 10pt;">
                    <strong>Mode de paiement:</strong> {{ ucfirst($payment->payment_method) }}<br>
                    <strong>Statut:</strong> {{ $payment->status === 'completed' ? 'Payé' : ucfirst($payment->status) }}
                    @if($payment->transaction_id)
                        <br><strong>Référence:</strong> {{ $payment->transaction_id }}
                    @endif
                </div>
            @else
                <div class="total-row">
                    <span>TOTAL À PAYER</span>
                    <span>{{ number_format($rental->total_price, 2, ',', ' ') }} €</span>
                </div>
            @endif
        </div>
    </div>
    
    <div class="terms-section">
        <h2 class="section-title">5. CONDITIONS GÉNÉRALES DE LOCATION</h2>
        
        @foreach($termsAndConditions as $category)
            <div class="terms-category">
                <div class="terms-title">{{ $category['title'] }}</div>
                <ul class="terms-list">
                    @foreach($category['items'] as $item)
                        <li class="terms-item">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
    
    <div class="signature-section">
        <h2 class="section-title">6. SIGNATURES</h2>
        <p style="text-align: center; font-style: italic; margin-bottom: 30px;">
            Les parties reconnaissent avoir lu et accepté les conditions du présent contrat.
        </p>
        
        <div class="signature-grid">
            <div class="signature-col">
                <div class="signature-title">LE PROPRIÉTAIRE</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $owner->name }}</div>
                <div>Date: {{ $generatedAt->format('d/m/Y') }}</div>
            </div>
            <div class="signature-col">
                <div class="signature-title">LE LOCATAIRE</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $renter->name }}</div>
                <div>Date: {{ $generatedAt->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        CarLocation - Application de location de véhicules entre particuliers<br>
        Ce contrat est généré automatiquement et a valeur d'accord entre les parties
    </div>
</body>
</html>