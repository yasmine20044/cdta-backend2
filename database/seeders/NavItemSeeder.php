<?php

namespace Database\Seeders;

use App\Models\NavItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NavItemSeeder extends Seeder
{
    public function run(): void
    {
        // Top-level items
        $accueil = NavItem::create(['label' => 'Accueil', 'url' => '/', 'order' => 1]);
        
        $cdta = NavItem::create([
            'label' => 'CDTA', 
            'url' => '#0', 
            'order' => 2,
            'has_intro_card' => true,
            'intro_card_image' => 'megamenu-1',
            'intro_card_button_label' => 'À propos du CDTA',
            'intro_card_url' => '/fr/cdta/a-propos-du-cdta/'
        ]);
        
        $rt = NavItem::create(['label' => 'Recherche et Technologie', 'url' => '#0', 'order' => 3]);
        
        $valorisation = NavItem::create([
            'label' => 'Valorisation', 
            'url' => '#', 
            'order' => 4,
            'has_intro_card' => true,
            'intro_card_image' => 'megamenu-3',
            'intro_card_button_label' => 'Valorisation',
            'intro_card_url' => '/fr/valorisation/'
        ]);
        
        $cdtaSoc = NavItem::create(['label' => 'CDTA & Société', 'url' => '#0', 'order' => 5]);
        
        $comm = NavItem::create([
            'label' => 'Communication', 
            'url' => '#0', 
            'order' => 6,
            'has_intro_card' => true,
            'intro_card_image' => 'megamenu-4',
            'intro_card_button_label' => 'À propos du CDTA',
            'intro_card_url' => '/fr/cdta/a-propos-du-cdta/'
        ]);

        // Children of CDTA
        $this->createChildren($cdta->id, 'Présentation du CDTA', [
            'Organigramme du CDTA', 'Mot du directeur', 'Vision & Missions', 
            'Fonctionnement du CDTA', 'Historique du CDTA', 'Cadre réglementaire'
        ]);
        $this->createChildren($cdta->id, 'La vie au CDTA', [
            'COS', 'Crèche', 'Accueil des invités', 'Programme Markazi'
        ]);

        // Children of Recherche et Technologie
        $this->createChildren($rt->id, 'Divisions', [
            'Microélectronique et Nanotechnologie', 'Architecture des Systèmes et Multimédias', 
            'Productique et Robotique', 'Milieux Ionisés et Lasers', 'Division Télécom'
        ]);
        $this->createChildren($rt->id, 'Unités de recherche', [
            'Unité de Recherche UROP', 'Unité de Recherche URNN'
        ]);
        $this->createChildren($rt->id, 'Plateformes Technologiques', [
            'Plateforme de Microsystème Electromécanique', 'Plateforme Technologique de Microfabrication', 
            'Plateforme de Prototypage', 'Plateforme de Projection Thermique', 'Réalité Augmentée'
        ]);

        // Children of Valorisation
        $this->createChildren($valorisation->id, 'Produits de la R&D', [
            'Logiciels', 'Publications', 'Brevets', 'Expertises', 'Consulting', 'Formation à la carte'
        ]);
        $this->createChildren($valorisation->id, 'Partenariat', [
            'Académique', 'Industriel', 'Organismes publics', 'Les Conventions', 'Les Contrats R&D'
        ]);
        $this->createChildren($valorisation->id, 'Entrepreneuriat', [
            'Incubateur', 'Filiales', 'Journées Challenges'
        ]);

        // Children of CDTA & Société
        $madinati = NavItem::create(['parent_id' => $cdtaSoc->id, 'label' => 'Programme Madinati', 'url' => '/' . Str::slug('Programme Madinati'), 'order' => 1]);
        NavItem::create(['parent_id' => $madinati->id, 'label' => 'Application web Queffa', 'url' => '/' . Str::slug('Application web Queffa'), 'is_external' => true]);
        
        $this->createChildren($cdtaSoc->id, null, [
            'Technologie & Citoyen', 'Portes ouvertes sur le CDTA', 
            'Caravane de la Science', 'Participation dans les Salons & Foires'
        ], 2);

        // Children of Communication
        $this->createChildren($comm->id, 'Évènements', [
            'CDTA évènements', 
            ['label' => "SENALAP'11", 'is_external' => true],
            ['label' => "MPM'24", 'is_external' => true],
            ['label' => "ICNAS'23", 'is_external' => true],
            'Communiqués', 'Actualité', 'Archives', 'Liens Utiles'
        ]);
        $this->createChildren($comm->id, 'À propos', [
            'CDTA dans les Articles de Presse', 'Vidéo passage TV', 
            'Enregistrements passage radio', 'Galerie multimédia', 'Bulletins du CDTA'
        ]);
        $this->createChildren($comm->id, 'Contact', [
            'Nous contacter', 'Coordonnées', 'Annuaire', 
            'Offres de services', 'Offres de stages et formations'
        ]);
    }

    private function createChildren($parentId, $sectionHeading, $items, $startOrder = 1)
    {
        foreach ($items as $index => $item) {
            $data = is_array($item) ? $item : ['label' => $item];
            $label = $data['label'];
            
            NavItem::create(array_merge([
                'parent_id' => $parentId,
                'section_heading' => $sectionHeading,
                'url' => '/' . Str::slug($label),
                'order' => $startOrder + $index
            ], $data));
        }
    }
}
