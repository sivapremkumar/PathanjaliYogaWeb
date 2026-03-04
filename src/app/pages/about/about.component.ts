import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Heart, History, Users, Target } from 'lucide-angular';

@Component({
    selector: 'app-about',
    standalone: true,
    imports: [CommonModule, LucideAngularModule],
    templateUrl: './about.component.html',
    styleUrls: ['./about.component.css']
})
export class AboutComponent implements OnInit {
    trustees: any[] = [];
    readonly History = History;
    readonly Users = Users;
    readonly Target = Target;
    readonly Heart = Heart;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.api.getTrustees().subscribe(res => {
            this.trustees = res;
        });
    }
}
