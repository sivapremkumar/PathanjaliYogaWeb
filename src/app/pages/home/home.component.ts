import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeroComponent } from '../../components/hero/hero.component';
import { LucideAngularModule, Heart, BookOpen, Users, ArrowRight } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-home',
    standalone: true,
    imports: [CommonModule, HeroComponent, LucideAngularModule, RouterModule],
    templateUrl: './home.component.html',
    styleUrls: ['./home.component.css']
})
export class HomeComponent {
    readonly Heart = Heart;
    readonly BookOpen = BookOpen;
    readonly Users = Users;
    readonly ArrowRight = ArrowRight;
}
