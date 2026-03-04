import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LucideAngularModule, Heart, Users, Calendar } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-hero',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './hero.component.html',
    styleUrls: ['./hero.component.css']
})
export class HeroComponent {
    readonly Heart = Heart;
    readonly Users = Users;
    readonly Calendar = Calendar;
}
