import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Heart, BookOpen, Users, Calendar, ArrowRight } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-programs',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './programs.component.html',
    styleUrls: ['./programs.component.css']
})
export class ProgramsListComponent implements OnInit {
    programs: any[] = [];
    readonly Heart = Heart;
    readonly BookOpen = BookOpen;
    readonly Users = Users;
    readonly Calendar = Calendar;
    readonly ArrowRight = ArrowRight;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.api.getNews().subscribe(res => {
            // For this demo, we'll use a mix of news and hardcoded programs
        });

        // Seed with dummy data for immediate visual
        this.programs = [
            { id: 1, title: 'Traditional Padhanjali Yoga', description: 'Daily morning sessions focused on Surya Namaskar and Pranayama.', type: 'Yoga', schedule: '6:00 AM - 7:30 AM', image: 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=800' },
            { id: 2, title: 'Yoga for Children', description: 'Fun and interactive sessions to improve concentration and posture in kids.', type: 'Yoga', schedule: '4:30 PM - 5:30 PM', image: 'https://images.unsplash.com/photo-1552196564-972d46387347?auto=format&fit=crop&q=80&w=800' },
            { id: 3, title: 'Social Welfare Awareness', description: 'Monthly workshops about health, hygiene, and community development.', type: 'Welfare', schedule: 'Monthly Weekends', image: 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=800' }
        ];
    }
}

