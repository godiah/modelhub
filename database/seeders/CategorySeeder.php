<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The model catalogue's categories, modelled on how CGTrader classifies 3D models: a short list of top-level
 * categories, each with sub-categories. Idempotent (matched by slug), so it can run on every deploy and add new
 * ones without touching what sellers already chose.
 */
class CategorySeeder extends Seeder
{
    public const TREE = [
        'Aircraft' => ['Aircraft Part', 'Commercial Aircraft', 'Helicopter', 'Historic Aircraft', 'Jet', 'Military Aircraft', 'Private Aircraft'],
        'Animals' => ['Bird', 'Dinosaur', 'Fish', 'Insect', 'Mammal', 'Reptile'],
        'Architectural' => ['Decoration', 'Door', 'Engineering', 'Fixture', 'Floor', 'Lighting', 'Street Elements', 'Window'],
        'Cars' => ['Antique Car', 'Concept Car', 'Luxury Car', 'Racing Car', 'Sport Car', 'SUV', 'Other Cars'],
        'Characters' => ['Child', 'Clothing', 'Fantasy Character', 'Human Anatomy', 'Man', 'Sci-Fi Character', 'Woman'],
        'Electronics' => ['Computer', 'Phone', 'Other Electronics'],
        'Exteriors' => ['Cityscape', 'Historic Exterior', 'House', 'Industrial Exterior', 'Landmark', 'Landscape', 'Office Building', 'Public Building', 'Sci-Fi Exterior', 'Skyscraper', 'Stadium', 'Street'],
        'Food' => ['Beverage', 'Fruit', 'Vegetable', 'Other Food'],
        'Furniture' => ['Appliance', 'Bed', 'Chair', 'Furniture Set', 'Kitchen Cabinet', 'Kitchen Furniture', 'Lamp', 'Outdoor Furniture', 'Sofa', 'Table', 'Tableware'],
        'Household' => ['Kitchenware', 'Household Tools', 'Other Household'],
        'Industrial' => ['Industrial Machine', 'Industrial Part', 'Tool'],
        'Interiors' => ['Bathroom', 'Bedroom', 'Hall', 'House Interior', 'Kitchen', 'Living Room', 'Office Interior'],
        'Military' => ['Armor', 'Gun', 'Melee Weapon', 'Military Character', 'Military Vehicle', 'Rocketry'],
        'Plants' => ['Bush', 'Conifer', 'Flower', 'Grass', 'Leaf', 'Pot Plant'],
        'Science' => ['Medical'],
        'Space' => ['Planet', 'Spaceship'],
        'Sports' => ['Game Equipment'],
        'Vehicles' => ['Bicycle', 'Bus', 'Industrial Vehicle', 'Motorcycle', 'Sci-Fi Vehicle', 'Train', 'Truck', 'Vehicle Part', 'Watercraft'],
        'Textures & Materials' => [],
        'Scripts & Plugins' => [],
        'Scanned Models' => [],
        'Various' => [],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::TREE as $parentName => $children) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($parentName)],
                ['name' => $parentName, 'parent_id' => null, 'position' => $position++, 'is_active' => true],
            );

            foreach (array_values($children) as $index => $childName) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($parentName.' '.$childName)],
                    ['name' => $childName, 'parent_id' => $parent->id, 'position' => $index, 'is_active' => true],
                );
            }
        }
    }
}
